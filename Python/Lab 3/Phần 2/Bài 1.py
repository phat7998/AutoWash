import threading

# Biến toàn cục để lưu tổng
total_sum = 0

# Lock để tránh race condition
lock = threading.Lock()

# Hàm tính tổng trong một đoạn số
def partial_sum(start, end):
    global total_sum
    local_sum = sum(range(start, end + 1))
    with lock:
        total_sum += local_sum

# Tạo các luồng
t1 = threading.Thread(target=partial_sum, args=(1, 50))
t2 = threading.Thread(target=partial_sum, args=(51, 100))

# Khởi động các luồng
t1.start()
t2.start()

# Chờ các luồng hoàn thành
t1.join()
t2.join()
print(f"Tổng từ 1 đến 100 là: {total_sum}")