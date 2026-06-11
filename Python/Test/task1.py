import xml.etree.ElementTree as ET

# Đọc và phân tích file XML
tree = ET.parse('Python/Lab 3/File/students.xml')
root = tree.getroot()

# 1. Hiển thị tất cả sinh viên
def display_all_students():
    print("\n--- Danh sách sinh viên ---")
    for student in root.findall('student'):
        id_ = student.find('id').text
        name = student.find('name').text
        age = student.find('age').text
        city = student.find('city').text
        print(f"ID: {id_}, Name: {name}, Age: {age}, City: {city}")

# 2. Hiển thị sinh viên theo city
def display_students_by_city(city_name):
    print(f"\n--- Sinh viên ở {city_name} ---")
    found = False
    for student in root.findall('student'):
        city = student.find('city').text
        if city.lower() == city_name.lower():
            id_ = student.find('id').text
            name = student.find('name').text
            age = student.find('age').text
            print(f"ID: {id_}, Name: {name}, Age: {age}, City: {city}")
            found = True
    if not found:
        print("Không tìm thấy sinh viên nào!")

# 3. Tìm sinh viên theo tên
def find_student_by_name(search_name):
    print(f"\n--- Tìm sinh viên theo tên chứa: {search_name} ---")
    found = False
    for student in root.findall('student'):
        name = student.find('name').text
        if search_name.lower() in name.lower():
            id_ = student.find('id').text
            age = student.find('age').text
            city = student.find('city').text
            print(f"ID: {id_}, Name: {name}, Age: {age}, City: {city}")
            found = True
    if not found:
        print("Không tìm thấy sinh viên!")

# 4. Sắp xếp sinh viên theo tuổi và ghi ra file mới
def sort_students_by_age_and_save():
    students = root.findall('student')
    students.sort(key=lambda s: int(s.find('age').text))
    
    new_root = ET.Element("students")
    for student in students:
        new_root.append(student)
    
    new_tree = ET.ElementTree(new_root)
    new_tree.write("sorted_students.xml", encoding="utf-8", xml_declaration=True)
    print("\nĐã lưu danh sách đã sắp xếp vào file sorted_students.xml")

# 5. Thêm sinh viên mới và ghi lại vào file
def add_student(id_, name, age, city):
    new_student = ET.Element("student")
    ET.SubElement(new_student, "id").text = id_
    ET.SubElement(new_student, "name").text = name
    ET.SubElement(new_student, "age").text = str(age)
    ET.SubElement(new_student, "city").text = city
    root.append(new_student)
    tree.write("student.xml", encoding="utf-8", xml_declaration=True)
    print(f"\nĐã thêm sinh viên {name} vào file student.xml")

def menu():
    while True:
        print("\n--- MENU ---")
        print("1. Hiển thị tất cả sinh viên")
        print("2. Hiển thị sinh viên theo city")
        print("3. Tìm sinh viên theo tên")
        print("4. Sắp xếp sinh viên theo tuổi và lưu file")
        print("5. Thêm sinh viên mới")
        print("6. Thoát")
        choice = input("Chọn chức năng (1-6): ")

        if choice == '1':
            display_all_students()
        elif choice == '2':
            city = input("Nhập tên thành phố: ")
            display_students_by_city(city)
        elif choice == '3':
            name = input("Nhập tên cần tìm: ")
            find_student_by_name(name)
        elif choice == '4':
            sort_students_by_age_and_save()
        elif choice == '5':
            id_ = input("ID: ")
            name = input("Name: ")
            age = input("Age: ")
            city = input("City: ")
            add_student(id_, name, age, city)
        elif choice == '6':
            print("Thoát chương trình.")
            break
        else:
            print("Lựa chọn không hợp lệ!")

if __name__ == "__main__":
    menu()