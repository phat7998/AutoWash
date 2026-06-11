#include <iostream>
#include <string>
#include <vector>
#include <algorithm>
using namespace std;

int main() {
    string str1, str2;
    cout << "Nhap chuoi str1: "; cin >> str1;
    cout << "Nhap chuoi str2: "; cin >> str2;

    int n = str1.length();
    int m = str2.length();

    // dp[i][j] luu do dai LCS cua str1[0..i-1] va str2[0..j-1]
    vector<vector<int>> dp(n + 1, vector<int>(m + 1, 0));

    for (int i = 1; i <= n; i++) {
        for (int j = 1; j <= m; j++) {
            if (str1[i - 1] == str2[j - 1]) {
                dp[i][j] = dp[i - 1][j - 1] + 1;
            } else {
                dp[i][j] = max(dp[i - 1][j], dp[i][j - 1]);
            }
        }
    }

    cout << "Do dai chuoi con chung dai nhat: " << dp[n][m] << endl;

    // --- Doan code truy vet de in ra chuoi ky tu con chung ---
    string lcs_str = "";
    int i = n, j = m;
    while (i > 0 && j > 0) {
        if (str1[i - 1] == str2[j - 1]) {
            lcs_str = str1[i - 1] + lcs_str;
            i--; j--;
        } else if (dp[i - 1][j] >= dp[i][j - 1]) {
            i--;
        } else {
            j--;
        }
    }
    cout << "Chuoi con chung do la: " << lcs_str << endl;

    return 0;
}