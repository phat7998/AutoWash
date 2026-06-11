import json
from datetime import datetime, timedelta
from collections import Counter

# Load dữ liệu từ file JSON
def load_data():
    with open('p:\\Python\\Python\\Lab 3\\File\\PythonGroup.json', 'r') as f:
        return json.load(f)

# Lưu dữ liệu vào file JSON
def save_data(data):
    with open('p:\\Python\\Python\\Lab 3\\File\\PythonGroup.json', 'w') as f:
        json.dump(data, f, indent=2)

# Hiển thị thông tin các bài viết
def display_posts(posts):
    print("\nThông tin các bài viết:")
    for post in posts:
        print(f"\nPost ID: {post['post_id']}")
        print(f"Author: {post['author']['name']}")
        print(f"Content: {post['content']}")
        print(f"Likes ({len(post['likes'])}): {[like['name'] for like in post['likes']]}")
        print(f"Shares ({len(post['shares'])}): {[share['name'] for share in post['shares']]}")

# Tính điểm cho bài viết dựa trên số lượng like và share
def calculate_score(post):
    return len(post['likes']) * 1 + len(post['shares']) * 2

# Bài viết có like cao nhất
def find_best_post(posts):
    best_post = max(posts, key=calculate_score)
    print("\nBài viết có like nhiều nhất:")
    print(f"Post ID: {best_post['post_id']}, Score: {calculate_score(best_post)}")

# Bài viết có nhiều like nhất trong 1 giờ
def likes_within_1h(post):
    post_time = datetime.strptime(post['timestamp'], '%Y-%m-%d %H:%M:%S')
    return sum(1 for like in post['likes'] if datetime.strptime(like['timestamp'], '%Y-%m-%d %H:%M:%S') <= post_time + timedelta(hours=1))

# Bài viết có nhiều like nhất trong 1 giờ
def find_most_liked_post_in_1h(posts):
    best_like_post = max(posts, key=likes_within_1h)
    print("\nBài viết có like nhiều nhất trogn 1 giờ:")
    print(f"Post ID: {best_like_post['post_id']}, Likes within 1h: {likes_within_1h(best_like_post)}")

# Thêm bài viết mới
def add_new_post(posts):
    post_id = input("Nhập ID bài viết: ")
    author_name = input("Nhập tên tác giả: ")
    author_id = input("Nhập ID tác giả: ")
    content = input("Nhập nội dung bài viết: ")
    timestamp = datetime.now().strftime('%Y-%m-%d %H:%M:%S')

    new_post = {
        "post_id": post_id,
        "author": {"user_id": author_id, "name": author_name},
        "content": content,
        "timestamp": timestamp,
        "likes": [],
        "shares": []
    }
    posts.append(new_post)
    print("\nThêm bài viết thành công")

# Người like/share nhiều nhất
def find_most_active_user(posts):
    user_counter = Counter()
    for post in posts:
        for like in post['likes']:
            user_counter[like['name']] += 1
        for share in post['shares']:
            user_counter[share['name']] += 1

    most_active_user, interactions = user_counter.most_common(1)[0]
    print("\nNgười có like/share nhiều nhất:")
    print(f"User: {most_active_user}, Total interactions: {interactions}")

# Hàm chính
def menu():

    data = load_data()
    posts = data['group']['posts']

    while True:
        print("\n------ MENU ------")
        print("1. Hiển thị thông tin các bài viết")
        print("2. Tìm bài viết có số like/share nhiều nhất")
        print("3. Tìm bài viết có nhiều like nhất trong 1h")
        print("4. Thêm bài viết mới")
        print("5. Tìm người like/share nhiều nhất")
        print("0. Thoát")

        choice = input("Nhập lựa chọn: ")

        if choice == '1':
            display_posts(posts)
        elif choice == '2':
            find_best_post(posts)
        elif choice == '3':
            find_most_liked_post_in_1h(posts)
        elif choice == '4':
            add_new_post(posts)
            save_data(data)
        elif choice == '5':
            find_most_active_user(posts)
        elif choice == '0':
            print("Thoát chương trình.")
            break
        else:
            print("Lựa chọn không hợp lệ.")

if __name__ == "__main__":
    menu()