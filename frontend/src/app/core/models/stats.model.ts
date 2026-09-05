export interface StatsSummary {
  from: string;
  to: string;
  salesCount: number;
  revenue: string;
  expensesTotal: string;
  estimatedProfit: string;
  balanceDue: string;
}

export interface TopProduct {
  productId: number;
  name: string;
  quantitySold: number;
  revenue: string;
}

export interface TopCustomer {
  customerId: number;
  name: string;
  salesCount: number;
  totalSpent: string;
  balanceDue: string;
}

export interface RevenuePoint {
  date: string;
  revenue: string;
}
