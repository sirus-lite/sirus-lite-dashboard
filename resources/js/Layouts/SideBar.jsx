

import { Sidebar } from "flowbite-react";
import { Link } from "@inertiajs/react";
import { HiChartPie, HiShoppingBag } from "react-icons/hi";

export default function SideBar() {
    return (
        <Sidebar aria-label="Sidebar with multi-level dropdown">
            <Sidebar.Items>
                <Sidebar.ItemGroup>
                    <Sidebar.Item icon={HiChartPie}>
                        <Link href={route('dashboard')}>RJ All</Link>
                    </Sidebar.Item>

                    <Sidebar.Item icon={HiChartPie}>
                        <Link href={route('RJ.PasienRawatJalanPoli')}>RJ / Poli</Link>
                    </Sidebar.Item>

                    <Sidebar.Item href={route('RJ.PasienRawatJalan')} icon={HiShoppingBag}>
                        Progress. . .
                    </Sidebar.Item>
                    <Sidebar.Collapse icon={HiShoppingBag} label="E-commerce">
                        <Sidebar.Item href="#">Products</Sidebar.Item>
                        <Sidebar.Item href="#">Sales</Sidebar.Item>
                        <Sidebar.Item href="#">Refunds</Sidebar.Item>
                        <Sidebar.Item href="#">Shipping</Sidebar.Item>
                    </Sidebar.Collapse>

                </Sidebar.ItemGroup>
            </Sidebar.Items>
        </Sidebar >
    );
}
