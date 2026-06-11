#include <iostream>
#include <string>
#include <vector>
#include <algorithm>
using namespace std;

int main() {
    string A, B, C;
    cout << "Nhap chuoi A: ";
    getline(cin, A);

    cout << "Nhap chuoi B: ";
    getline(cin, B);

    cout << "Nhap chuoi C: ";
    getline(cin, C);

    int n = A.length(), m = B.length(), p = C.length();

    // dp[i][j][k] luu do dai LCS cua A[0..i-1], B[0..j-1], C[0..k-1]
    vector<vector<vector<int>>> dp(n + 1, vector<vector<int>>(m + 1, vector<int>(p + 1, 0)));

    for (int i = 1; i <= n; i++) {
        for (int j = 1; j <= m; j++) {
            for (int k = 1; k <= p; k++) {
                if (A[i - 1] == B[j - 1] && B[j - 1] == C[k - 1]) {
                    dp[i][j][k] = dp[i - 1][j - 1][k - 1] + 1;
                } else {
                    dp[i][j][k] = max({dp[i - 1][j][k], 
                                       dp[i][j - 1][k], 
                                       dp[i][j][k - 1]});
                }
            }
        }
    }

    cout << "Do dai chuoi con chung dai nhat cua 3 chuoi la: " << dp[n][m][p] << endl;
    return 0;
}



