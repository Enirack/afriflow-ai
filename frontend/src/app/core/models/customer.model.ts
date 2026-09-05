export interface Customer {
  '@id'?: string;
  id: number;
  name: string;
  phone: string | null;
  email: string | null;
  address: string | null;
  createdAt: string;
}

export interface CreateCustomerPayload {
  name: string;
  phone?: string | null;
  email?: string | null;
  address?: string | null;
}
