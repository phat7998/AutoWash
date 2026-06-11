import mysql.connector
from datetime import datetime

class Enrollment:
    def __init__(self, mssv, course_id, registration_date):
        self.mssv = mssv
        self.course_id = course_id
        self.registration_date = registration_date

	# Định nghĩa các phương thức tương ứng với lệnh SQL insert, update, delete, select
    def enroll(self, db):
        query = "INSERT INTO enrollment (mssv, course_id, registration_date) VALUES (%s, %s, %s)"
        params = (self.mssv, self.course_id, self.registration_date)
        db.execute_query(query, params)

    @staticmethod
    def search_enrollment(db, mssv, course_id):
        query = "SELECT * FROM enrollment WHERE mssv = %s AND course_id = %s"
        result = db.fetch_all(query, (mssv, course_id))
        return result