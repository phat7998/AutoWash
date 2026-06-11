def chia_het_cho_5(nhi_phan):
  """Kiểm tra xem một số nhị phân có chia hết cho 5 hay không."""
  return int(nhi_phan, 2) % 5 == 0

def tim_so_chia_het_cho_5(chuoi_dau_vao):
  """Tìm các số nhị phân chia hết cho 5 từ chuỗi đầu vào."""
  cac_so = chuoi_dau_vao.split(',')
  ket_qua = [so for so in cac_so if chia_het_cho_5(so)]
  return ','.join(ket_qua)

# Nhập dữ liệu từ bàn phím
dau_vao = input("Nhập chuỗi các số nhị phân 4 chữ số, phân tách bởi dấu phẩy: ")

# Tìm và in kết quả
ket_qua = tim_so_chia_het_cho_5(dau_vao)
print(ket_qua)