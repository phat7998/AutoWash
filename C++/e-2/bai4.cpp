#include <iostream>
using namespace std;

// Dùng chia để trị để tính tổng toàn mảng
long long getSum(int a[], int left, int right) {
    if (left == right) return a[left];
    
    int mid = left + (right - left) / 2;
    return getSum(a, left, mid) + getSum(a, mid + 1, right);
}

int main() {
    int a[] = {4, 8, 15, 16, 23, 42};
    int n = sizeof(a) / sizeof(a[0]);
    
    long long totalSum = getSum(a, 0, n - 1);
    double average = (double)totalSum / n;
    
    cout << "Gia tri trung binh cua mang la: " << average << endl;
    return 0;
}