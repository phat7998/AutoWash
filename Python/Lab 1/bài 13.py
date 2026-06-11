# Hàm thêm sinh viên danh sách
def them_sinh_vien(danh_sach):
  mssv = input("Nhập MSSV: ")
  ho_ten = input("Nhập họ tên: ")
  diem_so = float(input("Nhập điểm số: "))
  sinh_vien = {"mssv": mssv, "ho_ten": ho_ten, "diem_so": diem_so}
  danh_sach.append(sinh_vien)
  print("Thêm sinh viên thành công!")

# Hàm hiển thị danh sách sinh viên
def hien_thi_danh_sach(danh_sach):
  """Hiển thị danh sách sinh viên."""
  if not danh_sach:
    print("Danh sách sinh viên trống.")
    return
  print("Danh sách sinh viên:")
  for sinh_vien in danh_sach:
    print(f"MSSV: {sinh_vien['mssv']}, Họ tên: {sinh_vien['ho_ten']}, Điểm số: {sinh_vien['diem_so']}")

# Hiển thị danh sách sinh viên có điểm số > 7
def hien_thi_sinh_vien_diem_cao(danh_sach):
  if not danh_sach:
    print("Danh sách sinh viên trống.")
    return
  print("Danh sách sinh viên có điểm số > 7:")
  for sinh_vien in danh_sach:
    if sinh_vien['diem_so'] > 7:
      print(f"MSSV: {sinh_vien['mssv']}, Họ tên: {sinh_vien['ho_ten']}, Điểm số: {sinh_vien['diem_so']}")

# Tìm kiếm sinh viên theo MSSV
def tim_kiem_sinh_vien(danh_sach):
  mssv_tim_kiem = input("Nhập MSSV cần tìm: ")
  for sinh_vien in danh_sach:
    if sinh_vien['mssv'] == mssv_tim_kiem:
      print(f"Thông tin sinh viên: MSSV: {sinh_vien['mssv']}, Họ tên: {sinh_vien['ho_ten']}, Điểm số: {sinh_vien['diem_so']}")
      return
  print("Không tìm thấy sinh viên có MSSV:", mssv_tim_kiem)

# Cập nhật điểm số của sinh viên
def cap_nhat_diem_so(danh_sach):
  mssv_cap_nhat = input("Nhập MSSV của sinh viên cần cập nhật điểm: ")
  for sinh_vien in danh_sach:
    if sinh_vien['mssv'] == mssv_cap_nhat:
      diem_moi = float(input("Nhập điểm số mới: "))
      sinh_vien['diem_so'] = diem_moi
      print("Cập nhật điểm số thành công!")
      return
  print("Không tìm thấy sinh viên có MSSV:", mssv_cap_nhat)

# Xóa sinh viên theo MSSV
def xoa_sinh_vien(danh_sach):
  mssv_xoa = input("Nhập MSSV của sinh viên cần xóa: ")
  for i, sinh_vien in enumerate(danh_sach):
    if sinh_vien['mssv'] == mssv_xoa:
      del danh_sach[i]
      print("Xóa sinh viên thành công!")
      return
  print("Không tìm thấy sinh viên có MSSV:", mssv_xoa)

# Hàm main
def main():
  danh_sach_sinh_vien = []
  while True:
    print("\n--- CHƯƠNG TRÌNH QUẢN LÝ SINH VIÊN ---")
    print("1. Thêm sinh viên")
    print("2. Hiển thị danh sách sinh viên")
    print("3. Hiển thị danh sách sinh viên có điểm > 7")
    print("4. Tìm kiếm sinh viên theo MSSV")
    print("5. Cập nhật điểm số sinh viên")
    print("6. Xóa sinh viên")
    print("7. Thoát")

    lua_chon = input("Nhập lựa chọn của bạn: ")

    if lua_chon == '1':
      them_sinh_vien(danh_sach_sinh_vien)
    elif lua_chon == '2':
      hien_thi_danh_sach(danh_sach_sinh_vien)
    elif lua_chon == '3':
      hien_thi_sinh_vien_diem_cao(danh_sach_sinh_vien)
    elif lua_chon == '4':
      tim_kiem_sinh_vien(danh_sach_sinh_vien)
    elif lua_chon == '5':
      cap_nhat_diem_so(danh_sach_sinh_vien)
    elif lua_chon == '6':
      xoa_sinh_vien(danh_sach_sinh_vien)
    elif lua_chon == '7':
      break
    else:
      print("Lựa chọn không hợp lệ. Vui lòng thử lại.")

if __name__ == "__main__":
  main()