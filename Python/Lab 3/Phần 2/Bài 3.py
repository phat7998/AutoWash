import threading
import random
import time

# Lớp Warehouse đại diện cho kho hàng
class Warehouse:
    # Khởi tạo kho hàng với số lượng sản phẩm ban đầu
    def __init__(self, initial_stock):
        self.stock = initial_stock
        self.lock = threading.Lock()
    
    # Phương thức bán hàng
    def sell(self, quantity):
        with self.lock:
            if self.stock >= quantity:
                self.stock -= quantity
                print(f"Bán {quantity} sản phẩm. Tồn kho còn lại: {self.stock}")
            else:
                print(f"Không đủ hàng để bán {quantity} sản phẩm. Tồn kho: {self.stock}")

    # Phương thức nhập hàng
    def restock(self, quantity):
        with self.lock:
            self.stock += quantity
            print(f"Nhập thêm {quantity} sản phẩm. Tồn kho hiện tại: {self.stock}")

""" Hàm mô phỏng hành vi của khách hàng
Mỗi khách hàng sẽ thực hiện 5 lần thao tác ngẫu nhiên """
def customer_behavior(warehouse):
    for _ in range(5):
        action = random.choice(['sell', 'restock'])
        quantity = random.randint(1, 10)
        if action == 'sell':
            warehouse.sell(quantity)
        else:
            warehouse.restock(quantity)
        time.sleep(random.uniform(0.5, 2))

# Hàm chính
def main():
    warehouse = Warehouse(initial_stock=50)

    threads = []
    for _ in range(3):  # 3 khách hàng cùng thao tác
        t = threading.Thread(target=customer_behavior, args=(warehouse,))
        t.start()
        threads.append(t)

    for t in threads:
        t.join()

if __name__ == "__main__":
    main()