# Nhập mảng 2 chiều từ người dùng
def nhap_mang_2d():

  hang = int(input("Nhập số hàng: "))
  cot = int(input("Nhập số cột: "))

  mang = []
  for i in range(hang):
    hang_hien_tai = []
    for j in range(cot):
      phan_tu = int(input(f"Nhập phần tử tại [{i}][{j}]: "))
      hang_hien_tai.append(phan_tu)
    mang.append(hang_hien_tai)
  return mang

# Kiểm tra xem một số có phải số nguyên tố hay không
def la_so_nguyen_to(n):

  if n <= 1:
    return False
  for i in range(2, int(n**0.5) + 1):
    if n % i == 0:
      return False
  return True

# Tính tổng các số nguyên tố trong mảng 2 chiều
def tinh_tong_so_nguyen_to(mang):

  tong = 0
  for hang in mang:
    for phan_tu in hang:
      if la_so_nguyen_to(phan_tu):
        tong += phan_tu
  return tong

# Tính tổng đường chéo chính của mảng vuông
def tinh_tong_duong_cheo_chinh(mang):

  if len(mang) != len(mang[0]):
    return "Mảng không phải là mảng vuông."

  tong = 0
  for i in range(len(mang)):
    tong += mang[i][i]
  return tong

# Tìm vị trí của phần tử nhỏ nhất và lớn nhất trong mảng 2 chiều
def tim_vi_tri_min_max(mang):

  if not mang:
    return "Mảng rỗng."

  min_val = mang[0][0]
  max_val = mang[0][0]
  min_pos = (0, 0)
  max_pos = (0, 0)

  for i in range(len(mang)):
    for j in range(len(mang[0])):
      if mang[i][j] < min_val:
        min_val = mang[i][j]
        min_pos = (i, j)
      if mang[i][j] > max_val:
        max_val = mang[i][j]
        max_pos = (i, j)
  return min_pos, max_pos

def main():

  mang_2d = nhap_mang_2d()

  tong_nt = tinh_tong_so_nguyen_to(mang_2d)
  print("Tổng các số nguyên tố trong mảng:", tong_nt)

  tong_cheo = tinh_tong_duong_cheo_chinh(mang_2d)
  print("Tổng đường chéo chính:", tong_cheo)

  min_pos, max_pos = tim_vi_tri_min_max(mang_2d)
  print(f"Vị trí phần tử nhỏ nhất: {min_pos}")
  print(f"Vị trí phần tử lớn nhất: {max_pos}")

if __name__ == "__main__":
  main()