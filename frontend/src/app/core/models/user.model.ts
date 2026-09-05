export interface Company {
  id: number;
  name: string;
  currency: string;
}

export interface CurrentUser {
  id: number;
  email: string;
  fullName: string;
  roles: string[];
  company: Company;
}
