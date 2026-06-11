#include <iostream>
#include <vector>
#include <algorithm>
using namespace std;

int main() {
    int n, k;
    cout << "Nhap N va K: ";
    cin >> n >> k;

    if (k < 0 || k > n) {
        cout << "Chinh hop khong ton tai (K phai >= 0 va K <= N)!" << endl;
        return 0;
    }

    if (k == 0) {
        cout << "Chinh hop chap 0 cua " << n << " la: 1" << endl;
        return 0;
    }

    // Tinh C(n, k) theo tam giac Pascal
    vector<vector<long long>> C(n + 1, vector<long long>(k + 1, 0));

    for (int i = 0; i <= n; ++i) {
        C[i][0] = 1;
        for (int j = 1; j <= min(i, k); ++j) {
            C[i][j] = C[i - 1][j - 1] + C[i - 1][j];
        }
    }

    // Tinh k!
    long long factK = 1;
    for (int i = 2; i <= k; ++i) {
        factK *= i;
    }

    // A(n, k) = k! * C(n, k)
    long long ans = factK * C[n][k];
    cout << "Chinh hop chap " << k << " cua " << n
         << " (A_" << n << "^" << k << ") la: " << ans << endl;

    return 0;
}