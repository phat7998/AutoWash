#include <iostream>
using namespace std;

double power(double a, int n) {
    // Điều kiện dừng
    if (n == 0) return 1;
    
    // Xử lý n âm
    if (n < 0) {
        a = 1 / a;
        n = -n;
    }

    // Chia để trị: Tính 1 nửa lũy thừa
    double half = power(a, n / 2);

    // Kết hợp
    if (n % 2 == 0) {
        return half * half;
    } else {
        return half * half * a;
    }
}

int main() {
    double a; int n;
    cout << "Nhap co so a va so mu n: ";
    cin >> a >> n;
    cout << "Gia tri " << a << "^" << n << " = " << power(a, n) << endl;
    return 0;
}