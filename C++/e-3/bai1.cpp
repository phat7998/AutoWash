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

    // L[i] luu do dai day con tang dai nhat ket thuc tai a[i]
    vector<int> L(n, 1);

    for (int i = 0; i < n; i++) {
        for (int j = 0; j < i; j++) {
            if (a[j] < a[i]) {
                L[i] = max(L[i], L[j] + 1);
            }
        }
    }

    // Ket qua la gia tri lon nhat trong mang L
    int ans = *max_element(L.begin(), L.end());
    cout << "Do dai day con tang dai nhat: " << ans << endl;

    return 0;
}