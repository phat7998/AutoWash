#include <iostream>
#include <vector>
#include <string>
using namespace std;

int nums[4];
int target_val;
char ops[3] = {'+', '-', '*'};
char chosen_ops[3];      
bool found_solution = false;

// Ham tinh gia tri bieu thuc theo dung quy tac toan hoc (Nhan chia truoc, cong tru sau)
int evaluateExpression() {
    vector<int> temp_nums;
    vector<char> temp_ops;
    
    temp_nums.push_back(nums[0]);
    
    // Xu ly cac phep tinh uu tien cao: phep nhan '*' truoc
    for (int i = 0; i < 3; i++) {
        if (chosen_ops[i] == '*') {
            int last_num = temp_nums.back();
            temp_nums.pop_back();
            temp_nums.push_back(last_num * nums[i + 1]);
        } else {
            temp_nums.push_back(nums[i + 1]);
            temp_ops.push_back(chosen_ops[i]);
        }
    }
    
    // Tinh toan cac phep cong '+' va tru '-' con lai tu trai qua phai
    int result = temp_nums[0];
    for (size_t i = 0; i < temp_ops.size(); i++) {
        if (temp_ops[i] == '+') result += temp_nums[i + 1];
        else if (temp_ops[i] == '-') result -= temp_nums[i + 1];
    }
    return result;
}

void backtrackOperators(int op_index) {
    if (op_index == 3) { // Da chon du 3 toan tu x1, x2, x3
        if (evaluateExpression() == target_val) {
            found_solution = true;
            cout << "Ket qua tim thay: " << nums[0] << " " << chosen_ops[0] << " " << nums[1] << " " << chosen_ops[1] << " " << nums[2] << " " << chosen_ops[2] << " " << nums[3] << " = " << target_val << endl;
            cout << "-> (x1) la " << chosen_ops[0] << ", (x2) la " << chosen_ops[1] << ", (x3) la " << chosen_ops[2] << endl;
        }
        return;
    }

    // Thu lan luot moi toan tu vao vi tri op_index
    for (int i = 0; i < 3; i++) {
        chosen_ops[op_index] = ops[i];
        backtrackOperators(op_index + 1);
    }
}

int main() {
    cout << "Nhap 4 so nguyen: ";
    for (int i = 0; i < 4; i++) {
        cin >> nums[i];
    }

    cout << "Nhap ket qua mong muon: ";
    cin >> target_val;

    cout << "Dang tim bieu thuc phu hop cho: ";
    for (int i = 0; i < 4; i++) {
        if (i > 0) cout << " ";
        cout << nums[i];
    }
    cout << " = " << target_val << "\n";

    backtrackOperators(0);
    
    if (!found_solution) {
        cout << "Khong co phuong an phu hop" << endl;
    }
    return 0;
}