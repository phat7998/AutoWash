import random

danh_sach = ["Tuyết", "Bảo", "Uyên", "Băng", "Nguyên", "Phii", "Tuấn", "Quàng"]
random.shuffle(danh_sach)

print("Thứ tự sau khi xáo trộn:")
for i, ten in enumerate(danh_sach, 1):
    print(f"{i}. {ten}")