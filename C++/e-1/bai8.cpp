#include <iostream>
using namespace std;

int x[6]; // Luu xau nhi phan do dai 5 (dung tu chi so 1 den 5)

void printBinary() {
    for (int i = 1; i <= 5; i++) cout << x[i];
    cout << endl;
}

void backtrackBinary(int i) {
    // Duyet cac gia tri co the dat vao bit i (0 hoac 1)
    for (int j = 0; j <= 1; j++) {
        // Dieu kien: Neu dat bit 9 0 thi bit ngay truoc do (i-1) phai khong phai la 0
        if (i > 1 && j == 0 && x[i - 1] == 0) {
            continue; // Vi pham dieu kien -> Bo qua nhanh nay
        }
        
        x[i] = j; // Dat bit
        
        if (i == 5) printBinary(); // Neu xep du 5 bit thi xuat ra
        else backtrackBinary(i + 1); // Chua du thi xep bit tiep theo
    }
}

int main() {
    cout << "Cac xau nhi phan do dai 5 khong co 2 bit 0 lien nhau:\n";
    backtrackBinary(1);
    return 0;
}