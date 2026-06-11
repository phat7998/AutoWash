import xml.etree.ElementTree as ET
import socket
import os

term_file = "Python/Lab 3/File/TermConf.xml"
net_file = "Python/Lab 3/File/NetworkConf.xml"

# Kiểm tra xem file có tồn tại không
def get_local_ip():
    try:
        return socket.gethostbyname(socket.gethostname())
    except:
        return "127.0.0.1"

# Hiển thị thông tin máy ATM
def show_term_info():
    tree = ET.parse(term_file)
    root = tree.getroot()
    term = root.find("Terminal")
    print(f"ID: {term.get('id')}")
    print(f"IP: {term.find('TermIP').text}")
    print(f"Branch: {term.find('branchNumber').text}")
    print(f"Type: {term.find('TerminalType').text}")

# Hiển thị thông tin host
def show_network_info():
    tree = ET.parse(net_file)
    root = tree.getroot()
    sessions = root.find("Sessions")
    for session in sessions.findall("NetworkSession"):
        name = session.get("name")
        protocol = session.get("protocol")
        enable = session.get("enable")
        host = session.find("NetworkAddress").get("hostIP")
        ip, port = host.split(":") if ":" in host else (host, "")
        status = "Đang kết nối" if enable == "1" else "Không kết nối"
        print(f"Name: {name}, IP: {ip}, Port: {port}, Protocol: {protocol}, Trạng thái: {status}")

# Cập nhật IP máy
def update_term_ip():
    ip = get_local_ip()
    tree = ET.parse(term_file)
    root = tree.getroot()
    root.find("Terminal").find("TermIP").text = ip
    tree.write(term_file, encoding="utf-8", xml_declaration=True)
    print(f"Đã cập nhật IP máy thành {ip}")

# Cập nhật thông tin TermConf.xml
def update_term_info():
    tree = ET.parse(term_file)
    root = tree.getroot()
    term = root.find("Terminal")
    term.set("id", input("ID mới: "))
    term.find("branchNumber").text = input("Branch Number: ")
    term.find("TerminalType").text = input("Terminal Type: ")
    term.find("autorestartmode").text = input("Auto restart mode (0/1): ")
    tree.write(term_file, encoding="utf-8", xml_declaration=True)
    print("Đã cập nhật thông tin TermConf.xml")

# Cập nhật thông tin host
def update_host_info():
    tree = ET.parse(net_file)
    root = tree.getroot()
    name = input("Nhập tên host cần sửa: ")
    protocol = input("Nhập protocol: ")
    sessions = root.find("Sessions").findall("NetworkSession")
    for session in sessions:
        if session.get("name") == name and session.get("protocol") == protocol:
            host_ip = input("IP: ")
            port = input("Port: ")
            desc = input("Mô tả (desc): ")
            session.find("NetworkAddress").set("hostIP", f"{host_ip}:{port}")
            session.set("desc", desc)
            break
    tree.write(net_file, encoding="utf-8", xml_declaration=True)
    print("Cập nhật host thành công.")

# Cập nhật thông tin SSL cho protocol NDC/DDC
def update_ssl_info():
    tree = ET.parse(net_file)
    root = tree.getroot()
    sessions = root.find("Sessions").findall("NetworkSession")
    for session in sessions:
        if session.get("protocol") == "NDC/DDC":
            security = session.find("Security")
            if security is not None:
                auth = security.find("Auth")
                auth.set("sslProtocol", input("sslProtocol (ví dụ: Tls12): "))
                auth.set("serverX509CertificateName", input("serverX509CertificateName: "))
                break
    tree.write(net_file, encoding="utf-8", xml_declaration=True)
    print("Cập nhật SSL thành công.")

# Hàm chính
def menu():
    while True:
        print("\n===== MENU =====")
        print("1. Hiển thị thông tin máy ATM (TermConf.xml)")
        print("2. Hiển thị thông tin host (NetworkConf.xml)")
        print("3. Cập nhật IP máy (tự động lấy IP)")
        print("4. Cập nhật ID, Branch, TerminalType, Auto restart")
        print("5. Cập nhật thông tin host (IP, Port, Desc)")
        print("6. Cập nhật SSL cho protocol NDC/DDC")
        print("0. Thoát")

        choice = input("Chọn chức năng: ")
        if choice == "1":
            show_term_info()
        elif choice == "2":
            show_network_info()
        elif choice == "3":
            update_term_ip()
        elif choice == "4":
            update_term_info()
        elif choice == "5":
            update_host_info()
        elif choice == "6":
            update_ssl_info()
        elif choice == "0":
            print("Thoát.")
            break
        else:
            print("Lựa chọn không hợp lệ.")

if __name__ == "__main__":
    menu()