def find_smallest_unreachable(arr):
    arr.sort()
    res = 1

    for num in arr:
        if num <= res:
            res += num
        else:
            break
    return res

start =  int(input("Nhập số bắt đâu: "))
end = int(input("Nhập số kết thúc: "))

arr = list(range(start, end + 1))

print("Danh sách các số nguyên là: ", arr)
print("Số nguyên dương nhỏ nhất không thể đạt được là: ", find_smallest_unreachable(arr))