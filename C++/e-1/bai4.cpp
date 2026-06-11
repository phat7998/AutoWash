#include <iostream>
using namespace std;

bool isPalindrome(int a[], int left, int right) {
    // Nếu chỉ số bên trái lớn hơn hoặc bằng bên phải -> đã kiểm tra xong hết và đối xứng
    if (left >= right) return true;
    
    // Nếu phát hiện cặp phần tử đối xứng không bằng nhau -> không đối xứng
    if (a[left] != a[right]) return false;
    
    // Đệ quy thu hẹp mảng vào phía trong
    return isPalindrome(a, left + 1, right - 1);
}

int main() {
    int n;
    cout << "Nhap so phan tu cua mang: ";
    cin >> n;
    int* a = new int[n];
    cout << "Nhap cac phan tu: ";
    for (int i = 0; i < n; i++) cin >> a[i];

    if (isPalindrome(a, 0, n - 1)) cout << "Mang doi xuong!" << endl;
    else cout << "Mang khong doi xuong!" << endl;

    delete[] a;
    return 0;
}