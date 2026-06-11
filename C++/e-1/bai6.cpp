#include <iostream>
#include <vector>
using namespace std;

int N, K;
vector<int> current_comb;
int a[100];

void printCombination() {
    cout << "{ ";
    for (int i = 0; i < K; i++) {
        cout << current_comb[i] << (i == K - 1 ? "" : ", ");
    }
    cout << " }\n";
}

void backtrackCombination(int start_index) {
    if (current_comb.size() == K) {
        printCombination();
        return;
    }

    for (int i = start_index; i <= N; i++) {
        current_comb.push_back(a[i]);
        backtrackCombination(i + 1);
        current_comb.pop_back();
    }
}

int main() {
    cout << "Nhap N va K: ";
    cin >> N >> K;
    cout << "Nhap " << N << " phan tu khac nhau: ";
    for (int i = 1; i <= N; i++) {
        cin >> a[i];
    }
    cout << "Nhap tap con gom " << K <<  " phan tu la: \n";
    backtrackCombination(1);
    return 0;
}