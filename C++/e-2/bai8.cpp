#include <iostream>
using namespace std;

// Phân hoạch tương tự Quick Sort
int partition(int a[], int low, int high) {
    int pivot = a[high];
    int i = low;
    for (int j = low; j < high; j++) {
        if (a[j] >= pivot) { // Tìm phần tử lớn thứ k nên phân hoạch giảm dần
            swap(a[i], a[j]);
            i++;
        }
    }
    swap(a[i], a[high]);
    return i;
}

// Hàm chia để trị tìm phần tử lớn thứ k (k tính từ 1)
int quickSelect(int a[], int low, int high, int k) {
    // Nếu k hợp lệ
    if (k > 0 && k <= high - low + 1) {
        int index = partition(a, low, high);

        // Nếu vị trí chốt đúng bằng vị trí thứ k cần tìm
        if (index - low == k - 1) return a[index];

        // Nếu vị trí cần tìm nằm bên trái chốt
        if (index - low > k - 1) {
            return quickSelect(a, low, index - 1, k);
        }

        // Ngược lại nằm bên phải chốt
        return quickSelect(a, index + 1, high, k - (index - low + 1));
    }
    return -1;
}

int main() {
    int a[] = {12, 3, 5, 7, 19, 4, 26};
    int n = sizeof(a) / sizeof(a[0]);
    int k = 3; // Cần tìm phần tử lớn thứ 3 trong mảng

    // Tạo một mảng phụ để tránh làm thay đổi mảng gốc khi phân hoạch
    int* temp = new int[n];
    for(int i=0; i<n; i++) temp[i] = a[i];

    int result = quickSelect(temp, 0, n - 1, k);
    cout << "Phan tu lon thu " << k << " trong mang la: " << result << endl;

    delete[] temp;
    return 0;
}