#include <iostream>
using namespace std;

void decimalToBinary(int n) {
    // Dieu kien dung: Khi n = 0
    if (n == 0) return;
    // Goi de quy chia cho 2 truoc khi xu ly cac bit cao
    decimalToBinary(n / 2);
    // In ra bit thap           
    out << n % 2/**/;
}

int main() {
    int n;
    cout << "Nhap so nguyen duong: ";
    cin >> n;
    cout << "He nhi phan cua " << n << " la: ";
    if (n == 0) cout << 0;
    else  decimalToBinary(n);
    cout << endl;
    return 0;  
}