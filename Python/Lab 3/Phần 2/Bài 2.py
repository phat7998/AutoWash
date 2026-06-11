import threading
import queue
import time

# Hàng đợi để chuyển số giữa các thread
even_queue = queue.Queue()
odd_queue = queue.Queue()

# Hàm đọc số từ file
def read_numbers(file_name):
    with open(file_name, 'r') as f:
        for line in f:
            number = int(line.strip())
            if number % 2 == 0:
                even_queue.put(number)
            else:
                odd_queue.put(number)
            time.sleep(1)

# Hàm tìm số nguyên tố
def prime_numbers():
    while True:
        number = even_queue.get()
        if number is None:
            break
        primes = [i for i in range(2, number+1) if all(i % j != 0 for j in range(2, int(i**0.5)+1))]
        print(f"Các số nguyên tố từ 1 đến {number}: {primes}")
        even_queue.task_done()
        time.sleep(1)

# Hàm tìm ước số
def divisors():
    while True:
        number = odd_queue.get()
        if number is None:
            break
        divisors_list = [i for i in range(1, number+1) if number % i == 0]
        print(f"Các ước số của {number}: {divisors_list}")
        odd_queue.task_done()
        time.sleep(1)

# Hàm main
def main():
    t1 = threading.Thread(target=read_numbers, args=("Python/Lab 3/File/num_file.txt",))
    t2 = threading.Thread(target=prime_numbers)
    t3 = threading.Thread(target=divisors)

    t2.start()
    t3.start()
    t1.start()

    t1.join()
    even_queue.put(None)  # Thông báo kết thúc cho thread 2
    odd_queue.put(None)   # Thông báo kết thúc cho thread 3

    t2.join()
    t3.join()

if __name__ == "__main__":
    main()