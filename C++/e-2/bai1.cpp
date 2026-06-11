#include <iostream>
using namespace std;

// Hàm trộn hai mảng con đã sắp xếp
void merge(int a[], int left, int mid, int right) {
    int n1 = mid - left + 1;
    int n2 = right - mid;

    int* L = new int[n1];
    int* R = new int[n2];

    for (int i = 0; i < n1; i++) L[i] = a[left + i];
    for (int j = 0; j < n2; j++) R[j] = a[mid + 1 + j];

    int i = 0, j = 0, k = left;
    while (i < n1 && j < n2) {
        if (L[i] <= R[j]) {
            a[k] = L[i];
            i++;
        } else {
            a[k] = R[j];
            j++;
        }
        k++;
    }

    while (i < n1) {
        a[k] = L[i];
        i++; k++;
    }
    while (j < n2) {
        a[k] = R[j];
        j++; k++;
    }

    delete[] L;
    delete[] R;
}

// Hàm chia để trị Merge Sort
void mergeSort(int a[], int left, int right) {
    if (left < right) {
        int mid = left + (right - left) / 2;
        mergeSort(a, left, mid);      // Chia nửa trái
        mergeSort(a, mid + 1, right); // Chia nửa phải
        merge(a, left, mid, right);   // Trộn lại
    }
}

int main() {
    int a[] = {11, 10, 19, 6, 20, 25, 15, 8, 7, 21, 1, 2}; // Giả định X = 15
    int n = sizeof(a) / sizeof(a[0]);

    cout << "Day so ban dau: ";
    for(int i=0; i<n; i++) cout << a[i] << " ";
    cout << endl;

    mergeSort(a, 0, n - 1);

    cout << "Day so sau khi xep (Merge Sort): ";
    for(int i=0; i<n; i++) cout << a[i] << " ";
    cout << endl;
    return 0;
}