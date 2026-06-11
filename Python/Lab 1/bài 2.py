''' 
import thư viện datatime để làm việc với ngày, giờ
date dùng để biểu diễn ngày, tháng, năm
'''
from datetime import date

# Tạo hàm để tính tuổi
def tinh_tuoi(ngay, thang, nam):
    
    # Lấy ngày hiện tại
    hom_nay = date.today()
    
    # Tạo ngày sinh từ thông tin người dùng nhập
    ngay_sinh = date(nam, thang, ngay)
    
    # Tính tuổi
    tuoi = hom_nay.year - ngay_sinh.year - ((hom_nay.month, hom_nay.day) < (ngay_sinh.month, ngay_sinh.day))
    
    return tuoi

# Nhập dữ liệu từ người dùng
ngay_sinh = int(input("Nhập ngày sinh: "))
thang_sinh = int(input("Nhập tháng sinh: "))
nam_sinh = int(input("Nhập năm sinh: "))

# Tính toán và in kết quả
tuoi = tinh_tuoi(ngay_sinh, thang_sinh, nam_sinh)
print("Tuổi của người đó là:", tuoi)