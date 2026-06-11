# Hàm tìm số chính phương
def so_chinh_phuong(n):
    if n < 0:
        return False
    sqrt_n = int(n**0.5)
    return sqrt_n * sqrt_n == n

# Hàm kiểm tra 1 số có ít nhất 2 số chẵn không
def hai_so_chan(n):
    even_count = 0
    for digit in str(n):
        if int(digit) % 2 == 0:
            even_count += 1
    return even_count >= 2

# Hàm tìm số chính từ 100 đến 2000
def find_numbers():
    result = []
    for num in range(100, 2000):
        if so_chinh_phuong(num) and hai_so_chan(num):
            result.append(num)
    return result

# In kết quả
numbers = find_numbers()
print(", ".join(map(str, numbers)))