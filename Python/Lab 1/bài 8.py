# Hàm đếm bao nhiêu chữ hoa và thường
def count_upper_lower(sentence):
    upper_count = 0
    lower_count = 0
    for char in sentence:
        if char.isupper():
            upper_count += 1
        elif char.islower():
            lower_count += 1
    return upper_count, lower_count

# Nhập dữ liệu
sentence = input("Nhập dữ liệu: ")

# In ra màn hình
upper, lower = count_upper_lower(sentence)
print("Số chữ hoa: ", upper)    
print("Số chữ thường: ", lower)
