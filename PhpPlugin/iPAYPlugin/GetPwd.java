import com.fss.plugin.ParseResouce;

public class GetPwd {
    public static void main(String[] args) throws Exception {
        System.out.println("Init New KS:");
        try { ParseResouce.loadNewKeyStore("nothing.bin"); } catch(Exception e) {}
        System.out.println("KS PWD: " + ParseResouce.KEYSTORE_PWD);
        System.out.println("KEY PWD: " + ParseResouce.KEY_PWD);
    }
}
