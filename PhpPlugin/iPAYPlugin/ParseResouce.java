/*
 * Decompiled with CFR 0.152.
 * 
 * Could not load the following classes:
 *  org.bouncycastle.jce.provider.BouncyCastleProvider
 */
package com.fss.plugin;

import com.fss.plugin.AESAlgorithm;
import com.fss.plugin.MAC;
import java.io.BufferedInputStream;
import java.io.ByteArrayOutputStream;
import java.io.EOFException;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.security.InvalidKeyException;
import java.security.Key;
import java.security.KeyStore;
import java.security.KeyStoreException;
import java.security.NoSuchAlgorithmException;
import java.security.Provider;
import java.security.Security;
import java.security.UnrecoverableKeyException;
import java.security.cert.CertificateException;
import java.util.zip.ZipEntry;
import java.util.zip.ZipFile;
import javax.crypto.BadPaddingException;
import javax.crypto.Cipher;
import javax.crypto.IllegalBlockSizeException;
import javax.crypto.NoSuchPaddingException;
import javax.crypto.ShortBufferException;
import org.bouncycastle.jce.provider.BouncyCastleProvider;

public class ParseResouce {
    public static final String SEPERATOR = File.separator;
    private static final String encrKekStr = "3535353330363331343038363230373832313932313537373433353833313137";
    private static final String encrKeystoreStr = "3c6440709a54bfa0932bedbca57df396dbc0df48f98b580a9ab91c1d84ecca69c0976abe59bd94974ea15824f46c4202";
    private static final String encrKeyStr = "3d6f4f729350bfa5972de6b1a47efd93ddcbd14afe8c520d9fbe1d1d81e2c96e70d592b22ee13e7443b8f2d222a01e45";
    public static Key DEK = null;
    public static String KEYSTORE_PWD = null;
    public static String KEY_PWD = null;

    static {
        Security.addProvider((Provider)new BouncyCastleProvider());
    }

    public static Key loadOldKeyStore(String keystorePath) throws Exception {
        Key key = null;
        key = ParseResouce.loadOldKeys(keystorePath);
        return key;
    }

    private static String getKeystorePwd() throws Exception {
        if (KEYSTORE_PWD == null) {
            KEYSTORE_PWD = new String(AESAlgorithm.decrypt(AESAlgorithm.generateAESKey(ParseResouce.hex2Alpha(encrKekStr)), AESAlgorithm.hexStringToByteArray(encrKeystoreStr)));
        }
        return KEYSTORE_PWD;
    }

    private static String getKeyPwd() throws Exception {
        if (KEY_PWD == null) {
            KEY_PWD = new String(AESAlgorithm.decrypt(AESAlgorithm.generateAESKey(ParseResouce.hex2Alpha(encrKekStr)), AESAlgorithm.hexStringToByteArray(encrKeyStr)));
        }
        return KEY_PWD;
    }

    public static Key loadNewKeyStore(String keystorePath) throws Exception {
        Key key = null;
        key = ParseResouce.loadNewKeys(keystorePath);
        return key;
    }

    private static Key loadOldKeys(String keystorelocation) throws KeyStoreException, NoSuchAlgorithmException, CertificateException, IOException, UnrecoverableKeyException, SecurityException, Exception {
        File file = new File(keystorelocation);
        Key key = null;
        if (!file.exists()) {
            return null;
        }
        KeyStore ks = KeyStore.getInstance("JCEKS");
        char[] pass = ParseResouce.getCharArray("password");
        try (FileInputStream is = null;){
            try {
                is = new FileInputStream(file);
                ks.load(is, pass);
                key = ks.getKey("pgkey", pass);
            }
            catch (EOFException ex) {
                ks.load(null, pass);
                if (is != null) {
                    ((InputStream)is).close();
                }
            }
        }
        return key;
    }

    private static Key loadNewKeys(String keystorelocation) throws KeyStoreException, NoSuchAlgorithmException, CertificateException, IOException, UnrecoverableKeyException, SecurityException, Exception {
        File file = new File(keystorelocation);
        Key key = null;
        KeyStore ks = KeyStore.getInstance("JCEKS");
        try (FileInputStream is = null;){
            try {
                is = new FileInputStream(file);
                ks.load(is, ParseResouce.getKeystorePwd().toCharArray());
                key = ks.getKey("pgkey", ParseResouce.getKeyPwd().toCharArray());
            }
            catch (EOFException ex) {
                ks.load(null, ParseResouce.getKeystorePwd().toCharArray());
                if (is != null) {
                    ((InputStream)is).close();
                }
            }
        }
        return key;
    }

    public static String hexString(byte[] abyte0) {
        StringBuffer stringbuffer = new StringBuffer(abyte0.length * 2);
        int i = 0;
        while (i < abyte0.length) {
            char c = Character.forDigit(abyte0[i] >>> 4 & 0xF, 16);
            char c1 = Character.forDigit(abyte0[i] & 0xF, 16);
            stringbuffer.append(Character.toUpperCase(c));
            stringbuffer.append(Character.toUpperCase(c1));
            ++i;
        }
        return stringbuffer.toString();
    }

    public static String hex2Alpha(String data) {
        int len = data.length();
        StringBuffer sb = new StringBuffer();
        int i = 0;
        while (i < len) {
            int j = i + 2;
            sb.append((char)Integer.valueOf(data.substring(i, j), 16).intValue());
            i += 2;
        }
        data = sb.toString();
        return data;
    }

    public static String alpha2Hex(String data) {
        char[] alpha = data.toCharArray();
        StringBuffer sb = new StringBuffer();
        int i = 0;
        while (i < alpha.length) {
            int count = Integer.toHexString(alpha[i]).toUpperCase().length();
            if (count <= 1) {
                sb.append("0").append(Integer.toHexString(alpha[i]).toUpperCase());
            } else {
                sb.append(Integer.toHexString(alpha[i]).toUpperCase());
            }
            ++i;
        }
        return sb.toString();
    }

    private static char[] getCharArray(String str) {
        return str != null && !str.equalsIgnoreCase("") ? str.toCharArray() : null;
    }

    public String getCGNData(String resourcePath, String aliasName, Key key) {
        try {
            String resp = this.createCGZFileFromCGN(resourcePath, key);
            if (resp != null && resp.contains("!!Exception!!")) {
                return resp;
            }
        }
        catch (InvalidKeyException e) {
            return new String("!!Exception!! while retrieving cgn data : " + e.getMessage() + MAC.fullErrorSplliter + MAC.fullStackTrace(e));
        }
        catch (NoSuchAlgorithmException e) {
            return new String("!!Exception!! while retrieving cgn data : " + e.getMessage() + MAC.fullErrorSplliter + MAC.fullStackTrace(e));
        }
        catch (NoSuchPaddingException e) {
            return new String("!!Exception!! while retrieving cgn data : " + e.getMessage() + MAC.fullErrorSplliter + MAC.fullStackTrace(e));
        }
        catch (ShortBufferException e) {
            return new String("!!Exception!! while retrieving cgn data : " + e.getMessage() + MAC.fullErrorSplliter + MAC.fullStackTrace(e));
        }
        catch (IllegalBlockSizeException e) {
            return new String("!!Exception!! while retrieving cgn data : " + e.getMessage() + MAC.fullErrorSplliter + MAC.fullStackTrace(e));
        }
        catch (BadPaddingException e) {
            return new String("!!Exception!! while retrieving cgn data : " + e.getMessage() + MAC.fullErrorSplliter + MAC.fullStackTrace(e));
        }
        catch (IOException e) {
            return new String("!!Exception!! while retrieving cgn data : " + e.getMessage() + MAC.fullErrorSplliter + MAC.fullStackTrace(e));
        }
        byte[] xmlData = null;
        byte[] decryptedText = null;
        try {
            xmlData = this.extractZIPAndReadXML(String.valueOf(aliasName) + ".xml", resourcePath);
        }
        catch (IllegalStateException e1) {
            return new String("!!Exception!! while extracting and reading xml data : " + e1.getMessage() + MAC.fullErrorSplliter + MAC.fullStackTrace(e1));
        }
        catch (IOException e1) {
            return new String("!!Exception!! while extracting and reading xml data : " + e1.getMessage() + MAC.fullErrorSplliter + MAC.fullStackTrace(e1));
        }
        if (xmlData == null || xmlData.length <= 0) {
            return new String("!!Exception!! Alias Name does not Exits." + MAC.fullErrorSplliter + "Record not found for this Alias Name " + aliasName + " in the resource file location : " + resourcePath);
        }
        try {
            byte[] keyByte = key.getEncoded();
            decryptedText = keyByte.length == 32 ? AESAlgorithm.decrypt(key, xmlData) : this.decrypt(xmlData, key);
        }
        catch (InvalidKeyException e) {
            return new String("!!Exception!! while decrypting xml data : " + e.getMessage() + MAC.fullErrorSplliter + MAC.fullStackTrace(e));
        }
        catch (NoSuchAlgorithmException e) {
            return new String("!!Exception!! while decrypting xml data : " + e.getMessage() + MAC.fullErrorSplliter + MAC.fullStackTrace(e));
        }
        catch (NoSuchPaddingException e) {
            return new String("!!Exception!! while decrypting xml data : " + e.getMessage() + MAC.fullErrorSplliter + MAC.fullStackTrace(e));
        }
        catch (ShortBufferException e) {
            return new String("!!Exception!! while decrypting xml data : " + e.getMessage() + MAC.fullErrorSplliter + MAC.fullStackTrace(e));
        }
        catch (IllegalBlockSizeException e) {
            return new String("!!Exception!! while decrypting xml data : " + e.getMessage() + MAC.fullErrorSplliter + MAC.fullStackTrace(e));
        }
        catch (BadPaddingException e) {
            return new String("!!Exception!! while decrypting xml data : " + e.getMessage() + MAC.fullErrorSplliter + MAC.fullStackTrace(e));
        }
        catch (Exception e) {
            return new String("!!Exception!! while decrypting xml data : " + e.getMessage() + MAC.fullErrorSplliter + MAC.fullStackTrace(e));
        }
        if (decryptedText != null) {
            try {
                this.deleteFile(String.valueOf(resourcePath) + SEPERATOR + "resource.cgz");
            }
            catch (Exception e) {
                return new String("!!Exception!! while deleting cgz file : " + e.getMessage() + MAC.fullErrorSplliter + MAC.fullStackTrace(e));
            }
            return new String(decryptedText);
        }
        return new String("!!Exception!! While decrypting xml data." + MAC.fullErrorSplliter + "Error while decrypting xml data or null");
    }

    public String getCGNDataAES(String resourcePath, String aliasName, Key key) {
        try {
            String resp = this.createCGZFileFromCGNAES(resourcePath, key);
            if (resp != null && resp.contains("!!Exception!!")) {
                return resp;
            }
        }
        catch (InvalidKeyException e) {
            return new String("!!Exception!! while retrieving AES cgn data : " + e.getMessage() + MAC.fullErrorSplliter + MAC.fullStackTrace(e));
        }
        catch (NoSuchAlgorithmException e) {
            return new String("!!Exception!! while retrieving AES cgn data : " + e.getMessage() + MAC.fullErrorSplliter + MAC.fullStackTrace(e));
        }
        catch (NoSuchPaddingException e) {
            return new String("!!Exception!! while retrieving AES cgn data : " + e.getMessage() + MAC.fullErrorSplliter + MAC.fullStackTrace(e));
        }
        catch (ShortBufferException e) {
            return new String("!!Exception!! while retrieving AES cgn data : " + e.getMessage() + MAC.fullErrorSplliter + MAC.fullStackTrace(e));
        }
        catch (IllegalBlockSizeException e) {
            return new String("!!Exception!! while retrieving AES cgn data : " + e.getMessage() + MAC.fullErrorSplliter + MAC.fullStackTrace(e));
        }
        catch (BadPaddingException e) {
            return new String("!!Exception!! while retrieving AES vcgn data : " + e.getMessage() + MAC.fullErrorSplliter + MAC.fullStackTrace(e));
        }
        catch (IOException e) {
            return new String("!!Exception!! while retrieving AES cgn data : " + e.getMessage() + MAC.fullErrorSplliter + MAC.fullStackTrace(e));
        }
        byte[] xmlData = null;
        byte[] decryptedText = null;
        try {
            xmlData = this.extractZIPAndReadXMLAES(String.valueOf(aliasName) + ".xml", resourcePath);
        }
        catch (IllegalStateException e1) {
            return new String("!!Exception!! while extracting and reading AES xml data : " + e1.getMessage() + MAC.fullErrorSplliter + MAC.fullStackTrace(e1));
        }
        catch (IOException e1) {
            return new String("!!Exception!! while extracting and reading AES xml data : " + e1.getMessage() + MAC.fullErrorSplliter + MAC.fullStackTrace(e1));
        }
        if (xmlData == null || xmlData.length <= 0) {
            return new String("!!Exception!! Alias Name does not Exits." + MAC.fullErrorSplliter + "Record not found for this Alias Name " + aliasName + " in the resource file location : " + resourcePath);
        }
        try {
            byte[] keyByte = key.getEncoded();
            decryptedText = keyByte.length == 32 ? AESAlgorithm.decrypt(key, xmlData) : this.decrypt(xmlData, key);
        }
        catch (InvalidKeyException e) {
            return new String("!!Exception!! while decrypting AES xml data : " + e.getMessage() + MAC.fullErrorSplliter + MAC.fullStackTrace(e));
        }
        catch (NoSuchAlgorithmException e) {
            return new String("!!Exception!! while decrypting AES xml data : " + e.getMessage() + MAC.fullErrorSplliter + MAC.fullStackTrace(e));
        }
        catch (NoSuchPaddingException e) {
            return new String("!!Exception!! while decrypting AES xml data : " + e.getMessage() + MAC.fullErrorSplliter + MAC.fullStackTrace(e));
        }
        catch (ShortBufferException e) {
            return new String("!!Exception!! while decrypting AES xml data : " + e.getMessage() + MAC.fullErrorSplliter + MAC.fullStackTrace(e));
        }
        catch (IllegalBlockSizeException e) {
            return new String("!!Exception!! while decrypting AES xml data : " + e.getMessage() + MAC.fullErrorSplliter + MAC.fullStackTrace(e));
        }
        catch (BadPaddingException e) {
            return new String("!!Exception!! while decrypting AES xml data : " + e.getMessage() + MAC.fullErrorSplliter + MAC.fullStackTrace(e));
        }
        catch (Exception e) {
            return new String("!!Exception!! while decrypting AES xml data : " + e.getMessage() + MAC.fullErrorSplliter + MAC.fullStackTrace(e));
        }
        if (decryptedText != null) {
            try {
                this.deleteFile(String.valueOf(resourcePath) + SEPERATOR + "AES_resource.cgz");
            }
            catch (Exception e) {
                return new String("!!Exception!! while deleting AES cgz file : " + e.getMessage() + MAC.fullErrorSplliter + MAC.fullStackTrace(e));
            }
            return new String(decryptedText);
        }
        return new String("!!Exception!! While decrypting AES xml data." + MAC.fullErrorSplliter + "Error while decrypting AES xml data or null");
    }

    private String createCGZFileFromCGN(String resourcePath, Key key) throws IOException, InvalidKeyException, NoSuchAlgorithmException, NoSuchPaddingException, ShortBufferException, IllegalBlockSizeException, BadPaddingException {
        String resp = null;
        File cgzFile = new File(String.valueOf(resourcePath) + SEPERATOR + "resource.cgz");
        if (!cgzFile.exists()) {
            cgzFile.createNewFile();
        }
        File cgnFile = new File(String.valueOf(resourcePath) + SEPERATOR + "resource.cgn");
        FileInputStream is = null;
        BufferedInputStream data = null;
        OutputStream os = null;
        try {
            try {
                is = new FileInputStream(cgnFile);
                data = new BufferedInputStream(is);
                os = new FileOutputStream(cgzFile);
                int cgnFileLength = (int)cgnFile.length();
                byte[] cgnFileData = new byte[cgnFileLength];
                while (data.read(cgnFileData) != -1) {
                }
                byte[] decryptedCgnFileData = null;
                byte[] keyByte = key.getEncoded();
                decryptedCgnFileData = keyByte.length == 32 ? AESAlgorithm.decrypt(key, cgnFileData) : this.decrypt(cgnFileData, key);
                os.write(decryptedCgnFileData);
                os.flush();
            }
            catch (Exception e) {
                resp = e instanceof FileNotFoundException ? "!!Exception!! File Not found in the Path Please validate the resource.cgn path : " + resourcePath + SEPERATOR + "resource.cgz : " + e.getMessage() + MAC.fullErrorSplliter + MAC.fullStackTrace(e) : "!!Exception!! in decrypting resource.cgn data : " + e.getMessage() + MAC.fullErrorSplliter + MAC.fullStackTrace(e);
                cgnFile = null;
                if (os != null) {
                    os.close();
                }
                if (data != null) {
                    data.close();
                }
                if (is != null) {
                    ((InputStream)is).close();
                }
            }
        }
        finally {
            cgnFile = null;
            if (os != null) {
                os.close();
            }
            if (data != null) {
                data.close();
            }
            if (is != null) {
                ((InputStream)is).close();
            }
        }
        return resp;
    }

    private String createCGZFileFromCGNAES(String resourcePath, Key key) throws IOException, InvalidKeyException, NoSuchAlgorithmException, NoSuchPaddingException, ShortBufferException, IllegalBlockSizeException, BadPaddingException {
        String resp = null;
        File cgzFile = new File(String.valueOf(resourcePath) + SEPERATOR + "AES_resource.cgz");
        if (!cgzFile.exists()) {
            cgzFile.createNewFile();
        }
        File cgnFile = new File(String.valueOf(resourcePath) + SEPERATOR + "AES_resource.cgn");
        FileInputStream is = null;
        BufferedInputStream data = null;
        OutputStream os = null;
        try {
            try {
                is = new FileInputStream(cgnFile);
                data = new BufferedInputStream(is);
                os = new FileOutputStream(cgzFile);
                int cgnFileLength = (int)cgnFile.length();
                byte[] cgnFileData = new byte[cgnFileLength];
                while (data.read(cgnFileData) != -1) {
                }
                byte[] decryptedCgnFileData = null;
                byte[] keyByte = key.getEncoded();
                decryptedCgnFileData = keyByte.length == 32 ? AESAlgorithm.decrypt(key, cgnFileData) : this.decrypt(cgnFileData, key);
                os.write(decryptedCgnFileData);
                os.flush();
            }
            catch (Exception e) {
                resp = e instanceof FileNotFoundException ? "!!Exception!! File Not found in the Path Please validate the resource.cgn path : " + resourcePath + SEPERATOR + "AES_resource.cgz : " + e.getMessage() + MAC.fullErrorSplliter + MAC.fullStackTrace(e) : "!!Exception!! in decrypting AES_resource.cgn data : " + e.getMessage() + MAC.fullErrorSplliter + MAC.fullStackTrace(e);
                cgnFile = null;
                if (os != null) {
                    os.close();
                }
                if (data != null) {
                    data.close();
                }
                if (is != null) {
                    ((InputStream)is).close();
                }
            }
        }
        finally {
            cgnFile = null;
            if (os != null) {
                os.close();
            }
            if (data != null) {
                data.close();
            }
            if (is != null) {
                ((InputStream)is).close();
            }
        }
        return resp;
    }

    private byte[] extractZIPAndReadXML(String entryName, String resourcePath) throws IOException, IllegalStateException {
        ByteArrayOutputStream os = new ByteArrayOutputStream();
        ZipFile zipFile = new ZipFile(String.valueOf(resourcePath) + SEPERATOR + "resource.cgz");
        ZipEntry entry = zipFile.getEntry(entryName);
        if (entry != null) {
            InputStream is = zipFile.getInputStream(entry);
            ParseResouce.copyInputStream(is, os);
        }
        zipFile.close();
        return os != null ? os.toByteArray() : null;
    }

    private byte[] extractZIPAndReadXMLAES(String entryName, String resourcePath) throws IOException, IllegalStateException {
        ByteArrayOutputStream os = new ByteArrayOutputStream();
        ZipFile zipFile = new ZipFile(String.valueOf(resourcePath) + SEPERATOR + "AES_resource.cgz");
        ZipEntry entry = zipFile.getEntry(entryName);
        if (entry != null) {
            InputStream is = zipFile.getInputStream(entry);
            ParseResouce.copyInputStream(is, os);
        }
        zipFile.close();
        return os != null ? os.toByteArray() : null;
    }

    public static final void copyInputStream(InputStream in, OutputStream out) throws IOException {
        int len;
        byte[] buffer = new byte[1024];
        while ((len = in.read(buffer)) >= 0) {
            out.write(buffer, 0, len);
        }
        in.close();
        out.close();
    }

    private byte[] decrypt(byte[] cipherText, Key key) throws NoSuchAlgorithmException, NoSuchPaddingException, InvalidKeyException, ShortBufferException, IllegalBlockSizeException, BadPaddingException {
        Cipher cipher = Cipher.getInstance("DESede/ECB/PKCS5Padding");
        cipher.init(2, key);
        int plainTextLength = cipher.getOutputSize(cipherText.length);
        byte[] tmpPlainText = new byte[plainTextLength];
        int ptLength = cipher.update(cipherText, 0, cipherText.length, tmpPlainText, 0);
        ptLength += cipher.doFinal(tmpPlainText, ptLength);
        byte[] plainText = new byte[ptLength];
        System.arraycopy(tmpPlainText, 0, plainText, 0, ptLength);
        return plainText;
    }

    private void deleteFile(String filePath) throws Exception {
        File file = new File(filePath);
        file.delete();
    }
}
