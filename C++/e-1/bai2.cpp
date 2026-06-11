#include <iostream>
#include <string>
using namespace std;

void printReverse(const string& s, int index) {
    // Dieu kien dung: Khi chuoi rong
    if (index ==  s.length()) return;
    // Goi de quy chho ky tu tiep theo truoc
    printReverse(s, index + 1);
    // In ky tu hien tai khi ham quay lui ve
    cout << s[index];
}

int main() {
    string s;
    cout << "Nhap dong ky tu: ";
    getline(cin, s);
    cout << "Dong ky tu dao nguoc la: ";
    printReverse(s, 0);
    cout << endl;
    return 0;
}