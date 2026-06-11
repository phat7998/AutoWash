def format_string(s):
  """Định dạng chuỗi theo yêu cầu."""
  # Loại bỏ khoảng trắng thừa ở đầu và cuối chuỗi
  s = s.strip()

  # Tách chuỗi thành danh sách các từ
  words = s.split()

  # Xử lý từng từ
  formatted_words = []
  for word in words:
    # Chuyển chữ cái đầu tiên thành chữ hoa, các chữ cái còn lại thành chữ thường
    formatted_word = word[0].upper() + word[1:].lower()
    formatted_words.append(formatted_word)

  # Nối các từ lại với nhau, chỉ có một khoảng trắng giữa các từ
  formatted_string = " ".join(formatted_words)

  return formatted_string

# Nhập chuỗi từ người dùng
input_string = input("Nhập vào một chuỗi: ")

# Định dạng chuỗi và in kết quả
output_string = format_string(input_string)
print("Chuỗi sau khi định dạng:", output_string)