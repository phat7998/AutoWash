#include <iostream>
using namespace std;

// Hàm phân hoạch lấy chốt là phần tử cuối cùng
int partition(int a[], int low, int high) {
    int pivot = a[high]; 
    int i = (low - 1); 

    for (int j = low; j <= high - 1; j++) {
        if (a[j] < pivot) {
            i++; 
            swap(a[i], a[j]);
        }
    }
    swap(a[i + 1], a[high]);
    return (i + 1);
}

// Hàm chia để trị Quick Sort
void quickSort(int a[], int low, int high) {
    if (low < high) {
        int pi = partition(a, low, high); // Vị trí chốt sau khi phân mạch
        quickSort(a, low, pi - 1);        // Đệ quy nửa trước chốt
        quickSort(a, pi + 1, high);       // Đệ quy nửa sau chốt
    }
}

int main() {
    int a[] = {15, 10, 9, 5, 20, 14, 8, 6, 21, 1}; // Giả định X = 14
    int n = sizeof(a) / sizeof(a[0]);

    cout << "Day so ban dau: ";
    for(int i=0; i<n; i++) cout << a[i] << " ";
    cout << endl;

    quickSort(a, 0, n - 1);

    cout << "Day so sau khi xep (Quick Sort): ";
    for(int i=0; i<n; i++) cout << a[i] << " ";
    cout << endl;
    return 0;
}