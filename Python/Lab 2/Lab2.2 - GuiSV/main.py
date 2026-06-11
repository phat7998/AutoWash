from database import Database
from book import Book
from member import Member
from borrowing import Borrowing
from datetime import datetime, timedelta

def main():
    db = Database()
    
    while True:
        print("\n--- HỆ THỐNG QUẢN LÝ THƯ VIỆN ---")
        print("1. Thêm sách")
        print("2. Sửa thông tin sách")
        print("3. Tìm kiếm sách")
        print("4. Hiển thị danh sách sách")
        print("5. Thêm thành viên")
        print("6. Tìm kiếm thành viên")
        print("7. Mượn sách")
        print("8. Trả sách")
        print("9. Hiển thị sách đã mượn quá hạn")
        print("10. Thoát")

        choice = input("Chọn chức năng: ")
        
        if choice == "1":
            title = input("Nhập tên sách: ")
            author = input("Nhập tác giả: ")
            pages = int(input("Nhập số trang: "))
            year_published = int(input("Nhập năm xuất bản: "))
            status = int(input("Nhập trạng thái sách (0: có sẵn, 1: đã mượn): "))
            category = input("Nhập chủng loại sách: ")
            book = Book(None, title, author, pages, year_published, status, category)
            book.add_book(db)
            print("Thêm sách thành công!")

        elif choice == "2":
            book_id = int(input("Nhập ID sách: "))
            book = Book.search_book(db, book_id)
            if book:
                title = input("Nhập tên mới: ") or book['title']
                author = input("Nhập tác giả mới: ") or book['author']
                pages = int(input("Nhập số trang mới: ") or book['pages'])
                year_published = int(input("Nhập năm xuất bản mới: ") or book['year_published'])
                status = int(input("Nhập trạng thái mới (0: có sẵn, 1: đã mượn): ") or book['status'])
                category = input("Nhập chủng loại mới: ") or book['category']
                updated_book = Book(book_id, title, author, pages, year_published, status, category)
                updated_book.update_book(db)
                print("Cập nhật sách thành công!")
            else:
                print("Sách không tồn tại.")

        elif choice == "3":
            book_id = int(input("Nhập ID sách cần tìm: "))
            book = Book.search_book(db, book_id)
            if book:
                print(book)
            else:
                print("Sách không tồn tại.")

        elif choice == "4":
            books = Book.get_all_books(db)
            if books:
                print("\nDanh sách sách:")
                for book in books:
                    print(book)
            else:
                print("Không có sách nào trong thư viện.")

        elif choice == "5":  # Thêm thành viên
            name = input("Nhập tên thành viên: ")
            member = Member(None, name)
            member.add_member(db)
            print("Thêm thành viên thành công!")

        elif choice == "6":  # Tìm kiếm thành viên
            member_id = int(input("Nhập ID thành viên: "))
            member = Member.search_member(db, member_id)
            if member:
                print(member)
            else:
                print("Thành viên không tồn tại.")

        elif choice == "7":  # Mượn sách
            member_id = int(input("Nhập ID thành viên: "))
            book_id = int(input("Nhập ID sách cần mượn: "))

            book = Book.search_book(db, book_id)  # Tìm kiếm sách theo ID

            if book:
                borrow_date = datetime.today().strftime('%Y-%m-%d')
                return_date = (datetime.today() + timedelta(days=14)).strftime('%Y-%m-%d')  # Ngày trả sau 14 ngày

                borrowing = Borrowing(None, member_id, book_id, borrow_date, return_date)
                borrowing.borrow_book(db)
                print(f"Sách có ID {book_id} đã được mượn thành công. Ngày trả sách là {return_date}.")
            else:
                print(f"Sách có ID {book_id} không tồn tại hoặc không có sẵn.")

        elif choice == "8":  # Trả sách
            member_id = int(input("Nhập ID thành viên: "))
            book_id = int(input("Nhập ID sách cần trả: "))

            borrowing = Borrowing.search_borrowing(db, member_id, book_id)

            if borrowing:
                Borrowing.return_book(db, member_id, book_id)
                print("Sách đã được trả thành công!")
            else:
                print("Không tìm thấy giao dịch mượn sách.")

        elif choice == "9":  # Hiển thị sách đã mượn quá hạn
            overdue_books = Borrowing.get_overdue_books(db)
            if overdue_books:
                print("\nDanh sách sách mượn quá hạn:")
                for book in overdue_books:
                    print(book)
            else:
                print("Không có sách nào quá hạn.")

        elif choice == "10":  # Thoát
            print("Cảm ơn bạn đã sử dụng hệ thống.")
            break


if __name__ == "__main__":
    main()
