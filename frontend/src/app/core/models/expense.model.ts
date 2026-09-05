export type ExpenseCategory =
  | 'transport'
  | 'salary'
  | 'stock'
  | 'rent'
  | 'marketing'
  | 'suppliers'
  | 'other';

export const EXPENSE_CATEGORY_LABELS: Record<ExpenseCategory, string> = {
  transport: 'Transport',
  salary: 'Salaire',
  stock: 'Stock',
  rent: 'Loyer',
  marketing: 'Marketing',
  suppliers: 'Fournisseurs',
  other: 'Autre',
};

export interface Expense {
  '@id'?: string;
  id: number;
  category: ExpenseCategory;
  amount: string;
  description: string | null;
  expenseDate: string;
  createdAt: string;
}

export interface CreateExpensePayload {
  category: ExpenseCategory;
  amount: string;
  description?: string | null;
  expenseDate: string;
}
