from datetime import date, timedelta

class Borrowing:
    def __init__(self, borrowing_id, member_id, book_id, borrow_date, due_date, return_date=None):
        self.borrowing_id = borrowing_id
        self.member_id = member_id
        self.book_id = book_id
        self.borrow_date = borrow_date
        self.return_date = return_date

    def borrow_book(self, db):
        query = """
        INSERT INTO borrowing (member_id, book_id, borrow_date, return_date) 
        VALUES (%s, %s, %s, %s)
        """
        params = (self.member_id, self.book_id, self.borrow_date, self.return_date)
        db.execute_query(query, params)

    def return_book(self, db):
        query = "UPDATE borrowing SET return_date = %s WHERE borrowing_id = %s"
        params = (self.return_date, self.borrowing_id)
        db.execute_query(query, params)

    @staticmethod
    def get_overdue_books(db):
        query = """
        SELECT b.title, m.name, bo.borrow_date, bo.due_date
        FROM borrowing bo
        JOIN books b ON bo.book_id = b.book_id
        JOIN members m ON bo.member_id = m.member_id
        WHERE bo.return_date IS NULL AND bo.due_date < %s
        """
        today = date.today()
        return db.fetch_all(query, (today,))
