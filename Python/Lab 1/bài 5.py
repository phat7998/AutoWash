# Nhập chuỗi
nhap_chuoi = input("Nhập vào một chuỗi các từ, cách nhau bằng khoảng trắng: ")

# Tách chuỗi
danh_sach_tu = nhap_chuoi.split()

# Loại bỏ các từ trùng lặp
danh_sach_tu_khong_trung_lap = list(set(danh_sach_tu))

# Sắp xếp các từ theo bảng chữ cái
danh_sach_tu_sap_xep = sorted(danh_sach_tu_khong_trung_lap)

# Chuyển các từ bắt đầu từ 'A' hoặc 'a' thành chữ in hoa
danh_sach_tu_in_hoa = []
for i in danh_sach_tu_sap_xep:
    # startswith hàm kiểm tra chữ cái có bắt đầu bằng 'A' hoặc 'a' không
    if i.startswith(('A', 'a')):
        # append hàm thêm kết quả vào cuối, upper hàm in hoa
        danh_sach_tu_in_hoa.append(i.upper())
    else:
        danh_sach_tu_in_hoa.append(i)

# In ra kết quả
print("Kết quả là: ", danh_sach_tu_in_hoa)