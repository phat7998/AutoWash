class Course:
    def __init__(self, course_id, course_name, description, credits):
        self.course_id = course_id
        self.course_name = course_name
        self.description = description
        self.credits = credits
        
	# Định nghĩa các phương thức tương ứng với lệnh SQL insert, update, delete, select
    def add_course(self, db):
        query = "INSERT INTO courses (course_id, course_name, description, credits) VALUES (%s, %s, %s, %s)"
        params = (self.course_id, self.course_name, self.description, self.credits)
        db.execute_query(query, params)

    def update_course(self, db):
        query = "UPDATE courses SET course_name = %s, description = %s, credits = %s WHERE course_id = %s"
        params = (self.course_name, self.description, self.credits, self.course_id)
        db.execute_query(query, params)

    @staticmethod
    def delete_course(db, course_id):
        query = "DELETE FROM courses WHERE course_id = %s"
        params = (course_id,)
        db.execute_query(query, params)

    @staticmethod
    def search_course(db, course_id):
        query = "SELECT * FROM courses WHERE course_id = %s"
        params = (course_id,)
        result = db.fetch_one(query, params)
        return result if result else None

    @staticmethod
    def get_all_courses(db):
        query = "SELECT * FROM courses"
        result = db.fetch_all(query)
        return result if result else []