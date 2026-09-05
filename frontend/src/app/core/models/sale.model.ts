export type PaymentMethod = 'cash' | 'mobile_money' | 'bank_transfer' | 'other';

export const PAYMENT_METHOD_LABELS: Record<PaymentMethod, string> = {
  cash: 'Espèces',
  mobile_money: 'Mobile Money',
  bank_transfer: 'Virement',
  other: 'Autre',
};

export type SaleStatus = 'paid' | 'partially_paid' | 'unpaid';

export const SALE_STATUS_LABELS: Record<SaleStatus, string> = {
  paid: 'Payée',
  partially_paid: 'Partiellement payée',
  unpaid: 'Non payée',
};

export interface SaleItem {
  id: number;
  product: string;
  quantity: number;
  unitPrice: string;
  totalPrice: string;
}

export interface Payment {
  id: number;
  amount: string;
  method: PaymentMethod;
  paidAt: string;
}

export interface Seller {
  id: number;
  fullName: string;
}

export interface Sale {
  '@id'?: string;
  id: number;
  customer: string | null;
  seller: Seller;
  saleDate: string;
  paymentMethod: PaymentMethod;
  discount: string;
  status: SaleStatus;
  createdAt: string;
  items: SaleItem[];
  payments: Payment[];
  totalAmount: string;
  amountPaid: string;
  balanceDue: string;
}

export interface CreateSaleItemPayload {
  productId: number;
  quantity: number;
  unitPrice?: string | null;
}

export interface CreateSalePayload {
  customerId?: number | null;
  paymentMethod: PaymentMethod;
  discount: string;
  items: CreateSaleItemPayload[];
}

export interface CreatePaymentPayload {
  saleId: number;
  amount: string;
  method: PaymentMethod;
}
