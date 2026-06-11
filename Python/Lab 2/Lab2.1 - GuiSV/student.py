import mysql.connector
from datetime import datetime

class Student:
    def __init__(self, mssv, name, dob, email, phone, address):
        self.mssv = mssv
        self.name = name
        self.dob = dob
        self.email = email
        self.phone = phone
        self.address = address

# Định nghĩa các phương thức tương ứng với lệnh SQL insert, update, delete, select
    def add_student(self, db):
        query = "INSERT INTO students (mssv, name, dob, email, phone, address) VALUES (%s, %s, %s, %s, %s, %s)"
        params = (self.mssv, self.name, self.dob, self.email, self.phone, self.address)
        db.execute_query(query, params)
    def update_student(self, db):
        query = "UPDATE students SET name = %s, dob = %s, email = %s, phone = %s, address = %s WHERE mssv = %s"
        params = (self.name, self.dob, self.email, self.phone, self.address, self.mssv)
        db.execute_query(query, params)
    @staticmethod
    def search_student(db, mssv):
        query = "SELECT * FROM students WHERE mssv = %s"
        result = db.fetch_all(query, (mssv,))
        formatted_students = []
        for student in result:
            mssv, name, dob, email, phone, address = student
            formatted_dob = dob.strftime("%d-%m-%Y")  # Định dạng lại ngày tháng
            formatted_students.append((mssv, name, formatted_dob, email, phone, address))
        
        return formatted_students if formatted_students else []
    @staticmethod
    def get_all_students(db):
        query = "SELECT * FROM students"
        result = db.fetch_all(query)
        
        formatted_students = []
        for student in result:
            mssv, name, dob, email, phone, address = student
            formatted_dob = dob.strftime("%d-%m-%Y")  # Định dạng lại ngày tháng
            formatted_students.append((mssv, name, formatted_dob, email, phone, address))
        return formatted_students if formatted_students else []