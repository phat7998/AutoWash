# Thêm từ vào từ điển
def them_tu(tu_dien):
  tu_anh = input("Nhập từ tiếng Anh: ")
  nghia_viet = input("Nhập nghĩa tiếng Việt: ")
  tu_dien[tu_anh] = nghia_viet
  print("Đã thêm từ thành công!")

# Tra cứu nghĩa của từ tiếng Anh
def tra_cuu_tu(tu_dien):
  tu_anh = input("Nhập từ tiếng Anh cần tra cứu: ")
  if tu_anh in tu_dien:
    print(f"Nghĩa tiếng Việt của '{tu_anh}' là: {tu_dien[tu_anh]}")
  else:
    print(f"Không tìm thấy từ '{tu_anh}' trong từ điển.")

# Xóa từ khỏi từ điển
def xoa_tu(tu_dien):
  tu_anh = input("Nhập từ tiếng Anh cần xóa: ")
  if tu_anh in tu_dien:
    del tu_dien[tu_anh]
    print(f"Đã xóa từ '{tu_anh}' khỏi từ điển.")
  else:
    print(f"Không tìm thấy từ '{tu_anh}' trong từ điển.")

def main():
  tu_dien = {}
  while True:
    print("\n--- TỪ ĐIỂN ANH-VIỆT ---")
    print("1. Thêm từ")
    print("2. Tra cứu từ")
    print("3. Xóa từ")
    print("4. Thoát")

    lua_chon = input("Nhập lựa chọn của bạn: ")

    if lua_chon == '1':
      them_tu(tu_dien)
    elif lua_chon == '2':
      tra_cuu_tu(tu_dien)
    elif lua_chon == '3':
      xoa_tu(tu_dien)
    elif lua_chon == '4':
      break
    else:
      print("Lựa chọn không hợp lệ. Vui lòng thử lại.")

if __name__ == "__main__":
  main()