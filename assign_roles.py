import random

# Danh sách 8 tên người
names = [
    "Tuyết",
    "Bảo",
    "Uyên",
    "Băng",
    "Nguyên",
    "Phii",
    "Tuấn",
    "Quàng"
]

# Danh sách các vai trò cho Bước 5: Test
roles = [
    "Bạn 1 – Giới thiệu Bước 5: Test là gì?",
    "Bạn 2 – Nguyên tắc khi Test với người dùng",
    "Bạn 3 – Chuẩn bị cho Test (Preparation)",
    "Bạn 4 – Bắt đầu Test: Welcome & Context",
    "Bạn 5 – Quan sát & Lắng nghe (Observe & Listen)",
    "Bạn 6 – Đặt câu hỏi mở trong quá trình Test",
    "Bạn 7 – Debrief: Tổng kết sau khi Test",
    "Bạn 8 – Phân tích kết quả & Vòng lặp cải tiến"
]

# Xáo trộn thứ tự tên để phân chia vai trò ngẫu nhiên
random.shuffle(names)

# Phân chia vai trò
assignments = list(zip(names, roles))

# In ra phân chia vai trò
print("Phân chia nhiệm vụ cho Bước 5: Test")
for name, role in assignments:
    print(f"{name}: {role}")