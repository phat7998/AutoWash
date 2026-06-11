#include <iostream>
using namespace std;

// Di chuyen n dia tu coc nguon (A) sang coc dich (C) qua coc trung gian (B)
void towerOfHanoi(int n, char source, char destination, char auxiliary, int &step) {
    if (n == 1) {
        step++;
        cout << "Buoc " << step << ": Di chuyen dia 1 tu " << source << " -> " << destination << endl;
        return;
    }
    // Buoc 1: Di chuyen n-1 dia tu Nguon sang Trung gian
    towerOfHanoi(n - 1, source, auxiliary, destination, step);
    
    // Buoc 2: Di chuyen dia thu n tu Nguon sang Dich
    step++;
    cout << "Buoc " << step << ": Di chuyen dia " << n << " tu " << source << " -> " << destination << endl;
    
    // Buoc 3: Di chuyen n-1 dia tu Trung gian sang Dich
    towerOfHanoi(n - 1, auxiliary, destination, source, step);
}

int main() {
    int n = 5;
    int step = 0;
    cout << "Cac buoc giai bai toan Thap Ha Noi voi 5 dia:" << endl;
    towerOfHanoi(n, 'A', 'C', 'B', step);
    return 0;
}