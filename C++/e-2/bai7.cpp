#include <iostream>
using namespace std;

struct CountResult {
    int equalK;
    int betweenXY;
};

CountResult countDivideAndConquer(int a[], int left, int right, int k, int x, int y) {
    if (left == right) {
        CountResult r = {0, 0};
        if (a[left] == k) r.equalK = 1;
        if (a[left] > x && a[left] < y) r.betweenXY = 1;
        return r;
    }

    int mid = left + (right - left) / 2;
    
    // Gọi chia để trị sang 2 vế độc lập
    CountResult leftRes = countDivideAndConquer(a, left, mid, k, x, y);
    CountResult rightRes = countDivideAndConquer(a, mid + 1, right, k, x, y);

    // Gộp kết quả của 2 nhánh
    CountResult total;
    total.equalK = leftRes.equalK + rightRes.equalK;
    total.betweenXY = leftRes.betweenXY + rightRes.betweenXY;
    return total;
}

int main() {
    int a[] = {3, 7, 2, 8, 5, 7, 10, 4, 7, 1};
    int n = sizeof(a) / sizeof(a[0]);
    int k = 7, x = 2, y = 6;

    CountResult ans = countDivideAndConquer(a, 0, n - 1, k, x, y);

    cout << "So phan tu bang " << k << " la: " << ans.equalK << endl;
    cout << "So phan tu lon hon " << x << " va nho hon " << y << " la: " << ans.betweenXY << endl;

    return 0;
}