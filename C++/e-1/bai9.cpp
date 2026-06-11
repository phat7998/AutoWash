#include <iostream>
#include <vector>
#include <algorithm>
using namespace std;

int n, target;
int a[100];
vector<int> current_list;

void backtrackSum(int start_index, int current_sum) {
    // Neu tong dat dung bang gia tri B mong muon
    if (current_sum == target) {
        cout << "{ ";
        for (int i = 0; i < current_list.size(); i++) {
            cout << current_list[i] << (i == current_list.size() - 1 ? "" : ", ");
        }
        cout << " }\n";
        return;
    }

    // Neu tong hien tai da vuot qua B thi khong can tim tiep (cat nhanh)
    if (current_sum > target) return;

    // Cho phep duyet tu start_index de tranh lap cau hinh hoan vi (giam trung lap)
    for (int i = start_index; i < n; i++) {
        current_list.push_back(a[i]);
        
        // Vi duoc dung mot so nhieu lan nen tham so start_index van giu nguyen la i
        backtrackSum(i, current_sum + a[i]);
        
        current_list.pop_back(); // Quay lui
    }
}

int main() {
    cout << "Nhap so luong phan tu cua mang A: ";
    cin >> n;
    cout << "Nhap cac phan tu phan biet trong A: ";
    for (int i = 0; i < n; i++) cin >> a[i];
    cout << "Nhap gia tri tong B: ";
    cin >> target;

    // Sap xep mang de thuat toan chay toi uu va co thu tu dep
    sort(a, a + n);

    cout << "Cac to hop co tong bang " << target << " la:\n";
    backtrackSum(0, 0);
    return 0;
}