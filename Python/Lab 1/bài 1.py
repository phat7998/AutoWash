number = float(input("Nhập vào số giờ làm: "))

# Tiền lương < 40 giờ trên tuần là 25k
if (number <= 40):
    salary = number * 25
    print("Tiền lương của nhân viên trong 1 tuần là: ",salary,"VND")
# Tiền lương > 40 giờ trên tuần là 37.5k
else:
    salary = number * 37.5
    print("Tiền lương của nhân viên trong 1 tuần là: ",salary,"VND")    