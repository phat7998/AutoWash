#include <iostream>
using namespace std;

int N;
int res[100];
bool visited[100] = {false}; // Mang danh dau xem phan tu da duoc dung chua

void printPermutation() {
    for (int i = 1; i <= N; i++) cout << res[i] << " ";
    cout << endl;
}

void backtrackPermutation(int i) {
    for (int j = 1; j <= N; j++) {
        if (!visited[j]) { // Neu j chua duoc su dung
            res[i] = j;     // Chon j lam phan tu thu i
            visited[j] = true; // Danh dau da dung
            
            if (i == N) printPermutation(); // Da tim du so luong phan tu thi in
            else backtrackPermutation(i + 1); // Chua du thi tim phan tu tiep theo
            
            visited[j] = false; // Quay lui: Bo danh dau de dung cho cac nhanh khac
        }
    }
}

int main() {
    cout << "Nhap N: ";
    cin >> N;
    cout << "Tat ca hoán vi cua " << N << " phan tu la:\n";
    backtrackPermutation(1);
    return 0;
}