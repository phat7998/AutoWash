#include <iostream>
using namespace std;

int sumOfDigits(int n) {
    // Dieu kien dung: Neu n chi con 1 chu so
    if (n<10) return n;
    // Lay chu so cuoi cong voi tong cac chu so con lai
    return (n % 10) + sumOfDigits(n / 10);
}

int main() {
    int n;
    cout << "Nhap so nguyen duong: ";
    cin >> n;
    cout << "Tong cac chu so cua " << n << " la: " << sumOfDigits(n) << endl;
    return 0;
}