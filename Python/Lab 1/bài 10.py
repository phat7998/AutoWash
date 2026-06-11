# Hàm tìm file trùng lặp
def loai_bo_trung_lap(ten_file_dau_vao, ten_file_dau_ra):
  try:
    with open(ten_file_dau_vao, 'r', encoding='utf-8') as file_in:
      cac_bai_hat = file_in.readlines()
  except FileNotFoundError:
    print(f"Lỗi: Không tìm thấy file '{ten_file_dau_vao}'.")
    return

  # Loại bỏ ký tự xuống dòng và khoảng trắng thừa
  cac_bai_hat = [bai_hat.strip() for bai_hat in cac_bai_hat]

  # Sử dụng set để loại bỏ trùng lặp (set chỉ chứa các phần tử duy nhất)
  cac_bai_hat_khong_trung_lap = list(set(cac_bai_hat))

  try:
    with open(ten_file_dau_ra, 'w', encoding='utf-8') as file_out:
      for bai_hat in cac_bai_hat_khong_trung_lap:
        file_out.write(bai_hat + '\n')
  except IOError:
    print(f"Lỗi: Không thể ghi vào file '{ten_file_dau_ra}'.")

# Ví dụ sử dụng
ten_file_dau_vao = "danh_sach_bai_hat.txt"  # Thay đổi tên file nếu cần
ten_file_dau_ra = "danh_sach_bai_hat_khong_trung_lap.txt"  # Thay đổi tên file nếu cần

loai_bo_trung_lap(ten_file_dau_vao, ten_file_dau_ra)