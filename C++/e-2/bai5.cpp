#include <iostream>
#include <cstdlib>
#include <ctime>
#include <cmath>
using namespace std;

struct Result {
    int firstPosIdx; // Vị trí số dương đầu tiên (-1 nếu không thấy)
    int lastPrimeIdx; // Vị trí số nguyên tố cuối cùng (-1 nếu không thấy)
};

bool isPrime(int n) {
    if (n < 2) return false;
    for (int i = 2; i <= sqrt(n); i++) {
        if (n % i == 0) return false;
    }
    return true;
}

// Hàm chia để trị tìm kiếm đồng thời hai điều kiện
Result solve(int a[], int left, int right) {
    if (left == right) {
        Result r = {-1, -1};
        if (a[left] > 0) r.firstPosIdx = left;
        if (isPrime(a[left])) r.lastPrimeIdx = left;
        return r;
    }

    int mid = left + (right - left) / 2;
    Result resLeft = solve(a, left, mid);
    Result resRight = solve(a, mid + 1, right);

    Result finalRes;
    
    // Số dương đầu tiên: Ưu tiên chỉ số nhỏ hơn từ bên trái trước
    if (resLeft.firstPosIdx != -1) finalRes.firstPosIdx = resLeft.firstPosIdx;
    else finalRes.firstPosIdx = resRight.firstPosIdx;

    // Số nguyên tố cuối cùng: Ưu tiên chỉ số lớn hơn từ bên phải trước
    if (resRight.lastPrimeIdx != -1) finalRes.lastPrimeIdx = resRight.lastPrimeIdx;
    else finalRes.lastPrimeIdx = resLeft.lastPrimeIdx;

    return finalRes;
}

int main() {
    srand(time(0));
    int n;
    cout << "Nhap so phan tu N: ";
    cin >> n;
    
    int* a = new int[n];
    cout << "Mang ngau nhien sinh ra: ";
    for (int i = 0; i < n; i++) {
        a[i] = rand() % 50 - 15; // Sinh ngẫu nhiên từ -15 đến 34
        cout << a[i] << " ";
    }
    cout << endl;

    Result ans = solve(a, 0, n - 1);

    if (ans.firstPosIdx != -1) 
        cout << "So duong dau tien tai index " << ans.firstPosIdx << " co gia tri: " << a[ans.firstPosIdx] << endl;
    else 
        cout << "Khong co so duong nao trong mang." << endl;

    if (ans.lastPrimeIdx != -1) 
        cout << "So nguyen to cuoi cung tai index " << ans.lastPrimeIdx << " co gia tri: " << a[ans.lastPrimeIdx] << endl;
    else 
        cout << "Khong co so nguyen to nao trong mang." << endl;

    delete[] a;
    return 0;
}