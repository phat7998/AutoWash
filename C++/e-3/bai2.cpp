#include <iostream>
#include <vector>
#include <algorithm>
using namespace std;

int main() {
    int n;
    cout << "Nhap so phan tu N: ";
    cin >> n;
    vector<int> a(n);
    cout << "Nhap mang A: ";
    for (int i = 0; i < n; i++) cin >> a[i];

    if (n == 1) {
        cout << "Tong lon nhat: " << max(0, a[0]) << endl;
        return 0;
    }

    // dp[i] luu tong lon nhat xet tu phan tu thu 0 den i
    vector<int> dp(n);
    dp[0] = max(0, a[0]);
    dp[1] = max(dp[0], a[1]);

    for (int i = 2; i < n; i++) {
        // Co 2 lua chon: 
        // 1. Khong lay a[i] -> lay dp[i-1]
        // 2. Lay a[i] -> lay a[i] + dp[i-2] (vi khong duoc lay sat nhau)
        dp[i] = max(dp[i - 1], a[i] + dp[i - 2]);
    }

    cout << "Tong lon nhat cua day con khong ke nhau: " << dp[n - 1] << endl;
    return 0;
}