import xml.etree.ElementTree as ET

FILE_PATH = "Python/Lab 3/File/students.xml"

# Đọc file XML và trả về root và tree:
def load_students():
    tree = ET.parse(FILE_PATH)
    root = tree.getroot()
    return root, tree

# In thông tin của một sinh viên
def print_student(student):
    print(f"ID: {student.find('id').text}, Name: {student.find('name').text}, Age: {student.find('age').text}, City: {student.find('city').text}")

# Hiển thị tất cả sinh viên trong file XML
def show_all_students():
    root, _ = load_students()
    for student in root.findall('student'):
        print_student(student)

# Hiển thị sinh viên theo thành phố
def show_students_by_city():
    city = input("Nhập tên thành phố: ")
    root, _ = load_students()
    found = False
    for student in root.findall('student'):
        if student.find('city').text.lower() == city.lower():
            print_student(student)
            found = True
    if not found:
        print("Không tìm thấy sinh viên nào thuộc thành phố này.")

# Tìm sinh viên theo tên
def find_student_by_name():
    name = input("Nhập tên sinh viên: ")
    root, _ = load_students()
    found = False
    for student in root.findall('student'):
        if student.find('name').text.lower() == name.lower():
            print_student(student)
            found = True
    if not found:
        print("Không tìm thấy sinh viên.")

# Sắp xếp sinh viên theo tuổi và lưu vào file mới
def sort_by_age_and_save():
    root, _ = load_students()
    students = root.findall('student')
    students.sort(key=lambda s: int(s.find('age').text))

    new_root = ET.Element("students")
    for student in students:
        new_root.append(student)

    new_tree = ET.ElementTree(new_root)
    new_tree.write("Python/Lab 3/File/students_sorted.xml", encoding="utf-8", xml_declaration=True)
    print("Đã ghi danh sách đã sắp xếp theo tuổi vào 'students_sorted.xml'.")

# Thêm sinh viên mới vào file XML
def add_student():
    student_id = input("ID: ")
    name = input("Tên: ")
    age = input("Tuổi: ")
    city = input("Thành phố: ")

    root, tree = load_students()
    new_student = ET.Element("student")
    ET.SubElement(new_student, "id").text = student_id
    ET.SubElement(new_student, "name").text = name
    ET.SubElement(new_student, "age").text = age
    ET.SubElement(new_student, "city").text = city
    root.append(new_student)
    tree.write(FILE_PATH, encoding="utf-8", xml_declaration=True)
    print("Thêm sinh viên mới thành công.")

# Sửa thông tin sinh viên theo ID
def update_student():
    student_id = input("Nhập ID sinh viên cần sửa: ")
    root, tree = load_students()
    for student in root.findall('student'):
        if student.find('id').text == student_id:
            name = input("Tên mới (Enter để bỏ qua): ")
            age = input("Tuổi mới (Enter để bỏ qua): ")
            city = input("Thành phố mới (Enter để bỏ qua): ")
            if name: student.find('name').text = name
            if age: student.find('age').text = age
            if city: student.find('city').text = city
            tree.write(FILE_PATH, encoding="utf-8", xml_declaration=True)
            print("Cập nhật thông tin thành công.")
            return
    print("Không tìm thấy sinh viên.")

# Xóa sinh viên theo ID
def delete_student():
    student_id = input("Nhập ID sinh viên cần xóa: ")
    root, tree = load_students()
    for student in root.findall('student'):
        if student.find('id').text == student_id:
            root.remove(student)
            tree.write(FILE_PATH, encoding="utf-8", xml_declaration=True)
            print("Xóa sinh viên thành công.")
            return
    print("Không tìm thấy sinh viên.")

# Hàm chính
def menu():
    while True:
        print("\n====== MENU ======")
        print("1. Hiển thị tất cả sinh viên")
        print("2. Hiển thị sinh viên theo thành phố")
        print("3. Tìm sinh viên theo tên")
        print("4. Sắp xếp sinh viên theo tuổi và lưu vào file mới")
        print("5. Thêm sinh viên")
        print("6. Sửa thông tin sinh viên theo ID")
        print("7. Xóa sinh viên theo ID")
        print("0. Thoát")

        choice = input("Chọn chức năng: ")
        if choice == '1':
            show_all_students()
        elif choice == '2':
            show_students_by_city()
        elif choice == '3':
            find_student_by_name()
        elif choice == '4':
            sort_by_age_and_save()
        elif choice == '5':
            add_student()
        elif choice == '6':
            update_student()
        elif choice == '7':
            delete_student()
        elif choice == '0':
            print("Thoát chương trình.")
            break
        else:
            print("Lựa chọn không hợp lệ, vui lòng chọn lại.")

if __name__ == "__main__":
    menu()