class Member:
    def __init__(self, member_id, name):
        self.member_id = member_id
        self.name = name

    def add_member(self, db):
        query = "INSERT INTO members (name) VALUES (%s)"
        db.execute_query(query, (self.name,))

    def delete_member(self, db):
        query = "DELETE FROM members WHERE member_id = %s"
        db.execute_query(query, (self.member_id,))

    def update_member_info(self, db, new_name):
        query = "UPDATE members SET name = %s WHERE member_id = %s"
        db.execute_query(query, (new_name, self.member_id))
        self.name = new_name  # Cập nhật dữ liệu trong đối tượng hiện tại

    @staticmethod
    def search_member(db, member_id):
        query = "SELECT * FROM members WHERE member_id = %s"
        return db.fetch_one(query, (member_id,))

    @staticmethod
    def get_all_members(db):
        query = "SELECT * FROM members"
        return db.fetch_all(query)
