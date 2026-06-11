# Dùng module 're' để làm việc với biểu thức chính quy
import re

# Hàm để kiểm tra tính hợp lệ của username
def kiem_tra_username(username):
  if len(username) != 6:
    return False
  if not re.match(r'^[a-z0-9]{6}$', username):
    return False
  return True # Trả về True nếu username hợp lệ, False nếu không

# Hàm kiểm tra tính hợp lệ của password
def kiem_tra_password(password):
  
# Chuỗi kiểm tra password
  if not 6 <= len(password) <= 12:
    return False
  if not re.search(r'[a-z]', password):
    return False
  if not re.search(r'[A-Z]', password):
    return False
  if not re.search(r'[0-9]', password):
    return False
  if not re.search(r'[!@#$%^&*]', password):
    return False
  return True # Trả về True nếu password đúng, False nếu sai

# Hàm yêu cầu người dùng nhập username và password
def dang_ky():
  username = input("Nhập username: ")
  password = input("Nhập password: ")

  if kiem_tra_username(username):
    if kiem_tra_password(password):
      print("Đăng ký thành công!")
    else:
      print("Password không hợp lệ.")
  else:
    print("Username không hợp lệ.")

# Chạy chương trình
dang_ky()