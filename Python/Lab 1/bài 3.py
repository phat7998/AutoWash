# Vòng lặp này sẽ chạy từ 1 đến 9
for i in range(1, 10):
  danh_sach = []
  # Vòng lập này sẽ chạy từ 1 đến i, j là các số cần tính bình phương
  for j in range(1, i + 1):
    danh_sach.append(j**2) # append thêm j vào cuối danh sách
  print(danh_sach)