# Hàm ghi file giao dịch
def tinh_so_du():
    
    file_giao_dich = "Transaction.txt"
    file_ket_qua = "BalanceInquiry.txt"

    try:
        with open(file_giao_dich, 'r', encoding = 'utf-8') as file_in:
            cac_dong = file_in.readlines()
    except FileNotFoundError:
        print(f"Lỗi: Không tìm thấy file '{file_giao_dich}'.")
        return
    
    ten_khach_hang = ""
    so_du = 0

    for dong in cac_dong:
        dong = dong.strip() # Loại bỏ khoảng trắng thừa và ký tự xuống dòng
        if dong.startswith("Name: "):
            ten_khach_hang = dong[6: ] # Lấy tên khách hàng
        else: 
            try:
                loai_giao_dich, so_tien = dong.split()
                so_trien = int(so_tien)
                if loai_giao_dich == 'D': # Nộp tiền
                    so_du += so_tien
                elif loai_giao_dich == 'W': # Rút tiền
                    phi = so_tien * 0.001
                    so_du -= (so_tien + phi)
            except ValueError:
                print(f"Dòng không hợp lệ: '{dong}'")
    try:
        with open(file_ket_qua, 'w', encoding = 'utf-8') as file_out:
            file_out.write(f"Tên: {ten_khach_hang}\n")
            file_out.write(f"Số dư: {so_du}\n")
    except IOError:
        print(f"Lỗi: Không thể ghi vào file '{file_ket_qua}'.")

# In kết quả
tinh_so_du()