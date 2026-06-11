from database import Database
from course import Course
from student import Student
from enrollment import Enrollment

def main():
    db = Database()
    while True:
        print("\n=== HỆ THỐNG QUẢN LÝ ĐĂNG KÝ KHÓA HỌC ===")
        print("1. Thêm sinh viên")
        print("2. Sửa thông tin sinh viên")
        print("3. Tìm kiếm sinh viên")
        print("4. Hiển thị danh sách sinh viên")
        print("5. Thêm khóa học")
        print("6. Sửa thông tin khóa học")
        print("7. Tìm kiếm khóa học")
        print("8. Hiển thị khóa học")
        print("9. Đăng ký khóa học")
        print("10. Tìm kiếm thông tin đăng ký theo MSSV và khóa học")
        print("11. Thoát")

        choice = input("Chọn chức năng: ")
		# SV thêm code chạy được các chức năng của chương trình
        if choice == "1":
            mssv = input("Nhập MSSV: ")
            name = input("Nhập tên: ")
            dob = input("Nhập ngày sinh (YYYY-MM-DD): ")
            email = input("Nhập email: ")
            phone = input("Nhập điện thoại: ")
            address = input("Nhập địa chỉ: ")
            student = Student(mssv, name, dob, email, phone, address)
            student.add_student(db)

        elif choice == "2":
            mssv = input("Nhập MSSV: ")
            student = Student.search_student(db, mssv)
            if student:
                name = input("Nhập tên mới: ")
                dob = input("Nhập ngày sinh mới: ")
                email = input("Nhập email mới: ")
                phone = input("Nhập điện thoại mới: ")
                address = input("Nhập địa chỉ mới: ")
                updated_student = Student(mssv, name, dob, email, phone, address)
                updated_student.update_student(db)
            else:
                print("Sinh viên không tồn tại.")

        elif choice == "3":
            mssv = input("Nhập MSSV cần tìm: ")
            student = Student.search_student(db, mssv)
            if student:
                print(student)
            else:
                print("Sinh viên không tồn tại.")

        elif choice == "4":
            students = Student.get_all_students(db)
            if students:
                print("\nDanh sách sinh viên:")
                for student in students:
                    print(student)
            else:
                print("Không có sinh viên nào trong hệ thống.")
		
		# tương tự như phần sinh viên ở trên
        elif choice == "5":
            course_id = input("Nhập mã khóa học: ")
            course_name = input("Nhập tên khóa học: ")
            instructor = input("Nhập giảng viên: ")
            credits = input("Nhập số tín chỉ: ")
            course = Course(course_id, course_name, instructor, credits)
            course.add_course(db)
            

        elif choice == "6":
            course_id = input("Nhập mã khóa học: ")
            course = Course.search_course(db, course_id)
            if course:
                course_name = input("Nhập tên khóa học mới: ")
                instructor = input("Nhập giảng viên mới: ")
                credits = input("Nhập số tín chỉ mới: ")
                updated_course = Course(course_id, course_name, instructor, credits)
                updated_course.update_course(db)
            else:
                print("Khóa học không tồn tại.")

        elif choice == "7":
            course_id = input("Nhập mã khóa học: ")
            course = Course.search_course(db, course_id)
            if course:
                print(course)
            else:
                print("Khóa học không tồn tại.")
        elif choice == "8":
            courses = Course.get_all_courses(db)
            if courses:
                print("\nDanh sách khóa học:")
                for course in courses:
                    print(course)
            else:
                print("Không có khóa học nào trong hệ thống.")

        elif choice == "9":
            mssv = input("Nhập MSSV: ")
            course_id = input("Nhập mã khóa học: ")
            registration_date = input("Nhập ngày đăng ký (YYYY-MM-DD): ")

            # Kiểm tra xem MSSV có tồn tại không
            student = Student.search_student(db, mssv)
            if not student:
                print("Sinh viên không tồn tại. Vui lòng thêm sinh viên trước khi đăng ký.")
                continue

             # Kiểm tra xem mã khóa học có tồn tại không
            course = Course.search_course(db, course_id)
            if not course:
                print("Khóa học không tồn tại. Vui lòng kiểm tra lại.")
                continue

            enrollment = Enrollment(mssv, course_id, registration_date)
            enrollment.enroll(db)

        elif choice == "10":  # Tìm kiếm đăng ký khóa học
            mssv = input("Nhập MSSV: ")
            course_id = input("Nhập mã khóa học: ")
            enrollments = Enrollment.search_enrollment(db, mssv, course_id)
            if enrollments:
                print("\nThông tin đăng ký khóa học:")
                for enrollment in enrollments:
                    print(enrollment)
            else:
                print("Không tìm thấy đăng ký khóa học cho sinh viên này.")

        elif choice == "11":
            print("Thoát chương trình.")
            break

if __name__ == "__main__":
    main()