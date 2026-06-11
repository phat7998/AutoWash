#include <iostream>
#include <algorithm>
using namespace std;

int findMin(int a[], int left, int right) {
    // Điều kiện dừng: Nếu mảng con chỉ có 1 phần tử
    if (left == right) return a[left];
    
    int mid = left + (right - left) / 2;
    
    // Chia để trị tìm min của 2 nửa trái phải độc lập
    int minLeft = findMin(a, left, mid);
    int minRight = findMin(a, mid + 1, right);
    
    // Kết hợp kết quả
    return min(minLeft, minRight);
}

int main() {
    int a[] = {23, 5, 84, 12, 3, 91, 7};
    int n = sizeof(a) / sizeof(a[0]);
    cout << "Gia tri nho nhat trong mang la: " << findMin(a, 0, n - 1) << endl;
    return 0;
}