<?php

declare(strict_types=1);

use App\Database\MigrationDefinition;

return new MigrationDefinition('007_generalize_loyalty_credit_lots', [
    'DROP PROCEDURE IF EXISTS autowash_preflight_loyalty_adjustments',
    <<<'SQL'
    CREATE PROCEDURE autowash_preflight_loyalty_adjustments()
    BEGIN
        DECLARE done INT DEFAULT 0;
        DECLARE debit_id BIGINT UNSIGNED;
        DECLARE debit_user_id BIGINT UNSIGNED;
        DECLARE debit_points INT;
        DECLARE debit_created_at DATETIME;
        DECLARE credit_id BIGINT UNSIGNED;
        DECLARE credit_remaining INT;
        DECLARE allocated INT;
        DECLARE bad_id BIGINT UNSIGNED DEFAULT NULL;
        DECLARE message_text VARCHAR(128);
        DECLARE debits CURSOR FOR
            SELECT id, user_id, ABS(points_delta), created_at
            FROM loyalty_transactions
            WHERE type = 'adjust' AND points_delta < 0
            ORDER BY created_at, id;
        DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

        SELECT users.id INTO bad_id
        FROM users
        LEFT JOIN loyalty_transactions ON loyalty_transactions.user_id = users.id
        WHERE users.role = 'customer'
        GROUP BY users.id
        HAVING MAX(users.point_balance) <> COALESCE(SUM(loyalty_transactions.points_delta), 0)
        LIMIT 1;

        IF bad_id IS NOT NULL THEN
            SET message_text = CONCAT('LOYALTY_BACKFILL_LEDGER_MISMATCH_USER_', bad_id);
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = message_text;
        END IF;

        SET bad_id = NULL;
        SELECT id INTO bad_id
        FROM loyalty_transactions
        WHERE type = 'earn' AND points_delta <= 0
        LIMIT 1;

        IF bad_id IS NOT NULL THEN
            SET message_text = CONCAT('LOYALTY_BACKFILL_ZERO_CREDIT_', bad_id);
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = message_text;
        END IF;

        SET bad_id = NULL;
        SELECT loyalty_transactions.id INTO bad_id
        FROM loyalty_transactions
        LEFT JOIN loyalty_allocations
            ON loyalty_allocations.debit_transaction_id = loyalty_transactions.id
        WHERE loyalty_transactions.type IN ('redeem', 'expire')
        GROUP BY loyalty_transactions.id
        HAVING ABS(MAX(loyalty_transactions.points_delta))
            <> COALESCE(SUM(loyalty_allocations.points_allocated), 0)
        LIMIT 1;

        IF bad_id IS NOT NULL THEN
            SET message_text = CONCAT('LOYALTY_BACKFILL_INVALID_DEBIT_', bad_id);
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = message_text;
        END IF;

        SET bad_id = NULL;
        SELECT loyalty_transactions.id INTO bad_id
        FROM loyalty_transactions
        LEFT JOIN loyalty_allocations
            ON loyalty_allocations.earn_transaction_id = loyalty_transactions.id
        WHERE loyalty_transactions.type = 'earn'
        GROUP BY loyalty_transactions.id
        HAVING MAX(loyalty_transactions.remaining_points)
            <> MAX(loyalty_transactions.points_delta)
                - COALESCE(SUM(loyalty_allocations.points_allocated), 0)
        LIMIT 1;

        IF bad_id IS NOT NULL THEN
            SET message_text = CONCAT('LOYALTY_BACKFILL_INVALID_CREDIT_', bad_id);
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = message_text;
        END IF;

        SET bad_id = NULL;
        SELECT loyalty_allocations.id INTO bad_id
        FROM loyalty_allocations
        INNER JOIN loyalty_transactions AS debit
            ON debit.id = loyalty_allocations.debit_transaction_id
        INNER JOIN loyalty_transactions AS credit
            ON credit.id = loyalty_allocations.earn_transaction_id
        WHERE debit.type NOT IN ('redeem', 'expire')
           OR credit.type <> 'earn'
           OR debit.user_id <> credit.user_id
        LIMIT 1;

        IF bad_id IS NOT NULL THEN
            SET message_text = CONCAT('LOYALTY_BACKFILL_INVALID_ALLOCATION_', bad_id);
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = message_text;
        END IF;

        CREATE TEMPORARY TABLE autowash_credit_lot_simulation (
            id BIGINT UNSIGNED PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            remaining_points INT UNSIGNED NOT NULL,
            expires_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_credit_simulation_fefo (user_id, expires_at, created_at, id)
        ) ENGINE=InnoDB;

        INSERT INTO autowash_credit_lot_simulation (
            id, user_id, remaining_points, expires_at, created_at
        )
        SELECT id, user_id, remaining_points, expires_at, created_at
        FROM loyalty_transactions
        WHERE type = 'earn' AND remaining_points > 0;

        INSERT INTO autowash_credit_lot_simulation (
            id, user_id, remaining_points, expires_at, created_at
        )
        SELECT id, user_id, points_delta, NULL, created_at
        FROM loyalty_transactions
        WHERE type = 'adjust' AND points_delta > 0;

        SET done = 0;
        OPEN debits;
        debit_loop: LOOP
            FETCH debits INTO debit_id, debit_user_id, debit_points, debit_created_at;

            IF done = 1 THEN
                LEAVE debit_loop;
            END IF;

            WHILE debit_points > 0 DO
                SET credit_id = NULL;
                SELECT id, remaining_points INTO credit_id, credit_remaining
                FROM autowash_credit_lot_simulation
                WHERE user_id = debit_user_id
                  AND remaining_points > 0
                  AND (created_at < debit_created_at OR (created_at = debit_created_at AND id < debit_id))
                  AND (expires_at IS NULL OR expires_at > debit_created_at)
                ORDER BY expires_at IS NULL, expires_at, created_at, id
                LIMIT 1;

                IF credit_id IS NULL THEN
                    SET message_text = CONCAT('LOYALTY_BACKFILL_UNALLOCATABLE_ADJUST_', debit_id);
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = message_text;
                END IF;

                SET allocated = LEAST(debit_points, credit_remaining);
                UPDATE autowash_credit_lot_simulation
                SET remaining_points = remaining_points - allocated
                WHERE id = credit_id;
                SET debit_points = debit_points - allocated;
            END WHILE;
        END LOOP;
        CLOSE debits;

        SET bad_id = NULL;
        SELECT users.id INTO bad_id
        FROM users
        LEFT JOIN autowash_credit_lot_simulation
            ON autowash_credit_lot_simulation.user_id = users.id
        WHERE users.role = 'customer'
        GROUP BY users.id
        HAVING MAX(users.point_balance)
            <> COALESCE(SUM(autowash_credit_lot_simulation.remaining_points), 0)
        LIMIT 1;

        IF bad_id IS NOT NULL THEN
            SET message_text = CONCAT('LOYALTY_BACKFILL_LOT_MISMATCH_USER_', bad_id);
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = message_text;
        END IF;

        DROP TEMPORARY TABLE autowash_credit_lot_simulation;
    END
    SQL,
    'CALL autowash_preflight_loyalty_adjustments()',
    'DROP PROCEDURE autowash_preflight_loyalty_adjustments',
    'ALTER TABLE loyalty_transactions DROP CHECK chk_loyalty_transactions_type',
    'ALTER TABLE loyalty_transactions DROP CHECK chk_loyalty_transactions_delta',
    'ALTER TABLE loyalty_transactions DROP CHECK chk_loyalty_transactions_remaining',
    'ALTER TABLE loyalty_transactions DROP CHECK chk_loyalty_transactions_expiry_period',
    'ALTER TABLE loyalty_allocations DROP CHECK chk_loyalty_allocations_points',
    'ALTER TABLE loyalty_allocations DROP FOREIGN KEY fk_loyalty_allocations_earn',
    'ALTER TABLE loyalty_allocations RENAME COLUMN earn_transaction_id TO credit_transaction_id',
    'ALTER TABLE loyalty_allocations RENAME COLUMN points_allocated TO allocated_points',
    'ALTER TABLE loyalty_allocations RENAME INDEX idx_loyalty_allocations_earn TO idx_loyalty_allocations_credit',
    <<<'SQL'
    UPDATE loyalty_transactions
    SET
        type = CASE WHEN points_delta > 0 THEN 'adjust_credit' ELSE 'adjust_debit' END,
        remaining_points = CASE WHEN points_delta > 0 THEN points_delta ELSE NULL END,
        updated_at = CURRENT_TIMESTAMP
    WHERE type = 'adjust'
    SQL,
    'DROP PROCEDURE IF EXISTS autowash_backfill_loyalty_adjustments',
    <<<'SQL'
    CREATE PROCEDURE autowash_backfill_loyalty_adjustments()
    BEGIN
        DECLARE done INT DEFAULT 0;
        DECLARE debit_id BIGINT UNSIGNED;
        DECLARE debit_user_id BIGINT UNSIGNED;
        DECLARE debit_points INT;
        DECLARE debit_created_at DATETIME;
        DECLARE credit_id BIGINT UNSIGNED;
        DECLARE credit_remaining INT;
        DECLARE allocated INT;
        DECLARE message_text VARCHAR(128);
        DECLARE debits CURSOR FOR
            SELECT id, user_id, ABS(points_delta), created_at
            FROM loyalty_transactions
            WHERE type = 'adjust_debit'
            ORDER BY created_at, id;
        DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

        START TRANSACTION;
        OPEN debits;
        debit_loop: LOOP
            FETCH debits INTO debit_id, debit_user_id, debit_points, debit_created_at;

            IF done = 1 THEN
                LEAVE debit_loop;
            END IF;

            WHILE debit_points > 0 DO
                SET credit_id = NULL;
                SELECT id, remaining_points INTO credit_id, credit_remaining
                FROM loyalty_transactions
                WHERE user_id = debit_user_id
                  AND type IN ('earn', 'adjust_credit')
                  AND remaining_points > 0
                  AND (created_at < debit_created_at OR (created_at = debit_created_at AND id < debit_id))
                  AND (expires_at IS NULL OR expires_at > debit_created_at)
                ORDER BY expires_at IS NULL, expires_at, created_at, id
                LIMIT 1
                FOR UPDATE;

                IF credit_id IS NULL THEN
                    ROLLBACK;
                    SET message_text = CONCAT('LOYALTY_BACKFILL_UNALLOCATABLE_ADJUST_', debit_id);
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = message_text;
                END IF;

                SET allocated = LEAST(debit_points, credit_remaining);
                UPDATE loyalty_transactions
                SET remaining_points = remaining_points - allocated, updated_at = CURRENT_TIMESTAMP
                WHERE id = credit_id;
                INSERT INTO loyalty_allocations (
                    debit_transaction_id, credit_transaction_id, allocated_points, allocated_at
                ) VALUES (debit_id, credit_id, allocated, debit_created_at);
                SET debit_points = debit_points - allocated;
            END WHILE;
        END LOOP;
        CLOSE debits;
        COMMIT;
    END
    SQL,
    'CALL autowash_backfill_loyalty_adjustments()',
    'DROP PROCEDURE autowash_backfill_loyalty_adjustments',
    <<<'SQL'
    ALTER TABLE loyalty_allocations
        ADD CONSTRAINT fk_loyalty_allocations_credit
            FOREIGN KEY (credit_transaction_id) REFERENCES loyalty_transactions (id),
        ADD CONSTRAINT chk_loyalty_allocations_points CHECK (allocated_points > 0)
    SQL,
    <<<'SQL'
    ALTER TABLE loyalty_transactions
        ADD CONSTRAINT chk_loyalty_transactions_type CHECK (
            type IN ('earn', 'adjust_credit', 'redeem', 'expire', 'adjust_debit')
        ),
        ADD CONSTRAINT chk_loyalty_transactions_delta CHECK (
            (type = 'earn' AND points_delta > 0)
            OR (type = 'adjust_credit' AND points_delta > 0)
            OR (type IN ('redeem', 'expire', 'adjust_debit') AND points_delta < 0)
        ),
        ADD CONSTRAINT chk_loyalty_transactions_remaining CHECK (
            (
                type IN ('earn', 'adjust_credit')
                AND remaining_points IS NOT NULL
                AND remaining_points <= points_delta
            )
            OR (type IN ('redeem', 'expire', 'adjust_debit') AND remaining_points IS NULL)
        ),
        ADD CONSTRAINT chk_loyalty_transactions_expiry_period CHECK (
            (type = 'earn' AND earned_at IS NOT NULL AND expires_at IS NOT NULL AND expires_at > earned_at)
            OR (type = 'adjust_credit' AND earned_at IS NULL AND expires_at IS NULL)
            OR type IN ('redeem', 'expire', 'adjust_debit')
        )
    SQL,
    'DROP PROCEDURE IF EXISTS autowash_verify_loyalty_credit_lots',
    <<<'SQL'
    CREATE PROCEDURE autowash_verify_loyalty_credit_lots()
    BEGIN
        DECLARE bad_id BIGINT UNSIGNED DEFAULT NULL;
        DECLARE message_text VARCHAR(128);

        SELECT users.id INTO bad_id
        FROM users
        LEFT JOIN loyalty_transactions ON loyalty_transactions.user_id = users.id
        WHERE users.role = 'customer'
        GROUP BY users.id
        HAVING MAX(users.point_balance) <> COALESCE(SUM(loyalty_transactions.points_delta), 0)
            OR MAX(users.point_balance) <> COALESCE(SUM(
                CASE
                    WHEN loyalty_transactions.type IN ('earn', 'adjust_credit')
                    THEN loyalty_transactions.remaining_points
                    ELSE 0
                END
            ), 0)
        LIMIT 1;

        IF bad_id IS NOT NULL THEN
            SET message_text = CONCAT('LOYALTY_BACKFILL_FINAL_MISMATCH_USER_', bad_id);
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = message_text;
        END IF;

        SET bad_id = NULL;
        SELECT loyalty_transactions.id INTO bad_id
        FROM loyalty_transactions
        LEFT JOIN loyalty_allocations
            ON loyalty_allocations.debit_transaction_id = loyalty_transactions.id
        WHERE loyalty_transactions.type IN ('redeem', 'expire', 'adjust_debit')
        GROUP BY loyalty_transactions.id
        HAVING ABS(MAX(loyalty_transactions.points_delta))
            <> COALESCE(SUM(loyalty_allocations.allocated_points), 0)
        LIMIT 1;

        IF bad_id IS NOT NULL THEN
            SET message_text = CONCAT('LOYALTY_BACKFILL_FINAL_INVALID_DEBIT_', bad_id);
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = message_text;
        END IF;
    END
    SQL,
    'CALL autowash_verify_loyalty_credit_lots()',
    'DROP PROCEDURE autowash_verify_loyalty_credit_lots',
]);
